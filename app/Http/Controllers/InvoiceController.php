<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\AuditLog;
use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\DraftInvoiceFactory;
use App\Services\PdfService;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('client')
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('from_date')) {
            $query->where('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('issue_date', '<=', $request->to_date);
        }

        $invoices = $query->paginate(15)->withQueryString();
        $clients = Client::orderBy('name')->get();

        return view('invoices.index', compact('invoices', 'clients'));
    }

    public function create(Request $request)
    {
        $profile = BusinessProfile::current();
        $clients = Client::orderBy('name')->get();
        $catalogItems = CatalogItem::active()->orderBy('name')->get();

        $selectedClientId = $request->get('client_id');
        $type = $request->get('type', 'invoice'); // invoice, quote, credit_note

        // Next number preview
        $previewNumber = match ($type) {
            'quote' => $profile->quote_prefix . str_pad((string) $profile->next_quote_number, $profile->quote_number_digits, '0', STR_PAD_LEFT),
            'credit_note' => $profile->credit_note_prefix . str_pad((string) $profile->next_credit_note_number, $profile->credit_note_number_digits, '0', STR_PAD_LEFT),
            default => $profile->invoice_prefix . str_pad((string) $profile->next_invoice_number, $profile->invoice_number_digits, '0', STR_PAD_LEFT),
        };

        // Source invoice if creating from previous
        $sourceInvoice = null;
        if ($request->filled('from_invoice_id')) {
            $sourceInvoice = Invoice::with('items')->find($request->from_invoice_id);
        }

        return view('invoices.create', compact('profile', 'clients', 'catalogItems', 'selectedClientId', 'type', 'previewNumber', 'sourceInvoice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'nullable|string|max:50|unique:invoices,invoice_number',
            'type' => 'required|in:invoice,quote,credit_note',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'tax_inclusive' => 'boolean',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
            'payment_link' => 'nullable|url',
            'pdf_template' => ['required', Rule::in(array_keys(app(PdfService::class)->availableTemplates()))],
            'page_size' => 'required|in:a4,letter',
            'locale' => 'required|in:en,es',
            'show_signature' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_name' => 'nullable|string',
        ]);

        // Anti-duplicate protection: prevent duplicate invoices for the same client on the same date unless explicitly authorized
        if ($request->type === 'invoice' && !$request->boolean('allow_duplicate', false)) {
            $existingInvoice = Invoice::where('client_id', $request->client_id)
                ->where('type', 'invoice')
                ->whereDate('issue_date', $request->issue_date)
                ->where('status', '!=', 'void')
                ->first();

            if ($existingInvoice) {
                throw ValidationException::withMessages([
                    'client_id' => __('app.invoice_duplicate_rejected', ['number' => $existingInvoice->invoice_number, 'date' => $request->issue_date]),
                ]);
            }
        }

        $profile = BusinessProfile::current();

        $invoice = DB::transaction(function () use ($request, $profile) {
            $type = $request->type;

            if ($request->filled('invoice_number')) {
                $invoiceNumber = trim($request->invoice_number);

                // Auto-advance sequence and sync prefix/digits if user supplied a custom starting number
                if (preg_match('/^(.*?)(\d+)$/', $invoiceNumber, $matches)) {
                    $prefix = $matches[1];
                    $numericPart = (int) $matches[2];
                    $digits = strlen($matches[2]);

                    if ($type === 'quote') {
                        $profile->update([
                            'next_quote_number' => $numericPart + 1,
                            'quote_prefix' => $prefix,
                            'quote_number_digits' => max($digits, $profile->quote_number_digits),
                        ]);
                    } elseif ($type === 'credit_note') {
                        $profile->update([
                            'next_credit_note_number' => $numericPart + 1,
                            'credit_note_prefix' => $prefix,
                            'credit_note_number_digits' => max($digits, $profile->credit_note_number_digits),
                        ]);
                    } else {
                        $profile->update([
                            'next_invoice_number' => $numericPart + 1,
                            'invoice_prefix' => $prefix,
                            'invoice_number_digits' => max($digits, $profile->invoice_number_digits),
                        ]);
                    }
                }
            } else {
                $invoiceNumber = match ($type) {
                    'quote' => $profile->generateNextQuoteNumber(),
                    'credit_note' => $profile->generateNextCreditNoteNumber(),
                    default => $profile->generateNextInvoiceNumber(),
                };
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'type' => $type,
                'status' => 'draft',
                'client_id' => $request->client_id,
                'parent_invoice_id' => $request->parent_invoice_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'currency' => strtoupper($request->currency),
                'exchange_rate' => $request->filled('exchange_rate') ? $request->exchange_rate : 1.000000,
                'tax_inclusive' => $request->boolean('tax_inclusive', false),
                'shipping_fee_cents' => (int) round(((float)$request->shipping_fee) * 100),
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'payment_instructions' => $request->payment_instructions,
                'payment_link' => $request->payment_link,
                'pdf_template' => $request->pdf_template,
                'page_size' => $request->page_size,
                'locale' => $request->locale,
                'show_signature' => $request->has('show_signature') ? $request->boolean('show_signature') : null,
            ]);

            foreach ($request->items as $index => $itemData) {
                $discountBps = isset($itemData['discount_percent']) ? (int) round($itemData['discount_percent'] * 100) : 0;
                $taxRateBps = isset($itemData['tax_rate_percent']) ? (int) round($itemData['tax_rate_percent'] * 100) : 0;
                $unitPriceCents = (int) round(((float)$itemData['unit_price']) * 100);

                $invoice->items()->create([
                    'sort_order' => $index,
                    'catalog_item_id' => $itemData['catalog_item_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price_cents' => $unitPriceCents,
                    'discount_bps' => $discountBps,
                    'tax_rate_bps' => $taxRateBps,
                    'tax_name' => $itemData['tax_name'] ?? null,
                ]);
            }

            $invoice->recalculateTotals();

            AuditLog::log($invoice->id, 'created', ['number' => $invoiceNumber, 'type' => $type]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', __('app.invoice_created', ['number' => $invoice->invoice_number]));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'payments', 'auditLogs', 'parentInvoice']);
        $profile = BusinessProfile::current();

        return view('invoices.show', compact('invoice', 'profile'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', __('app.invoice_locked_for_editing'));
        }

        $profile = BusinessProfile::current();
        $clients = Client::orderBy('name')->get();
        $catalogItems = CatalogItem::active()->orderBy('name')->get();

        return view('invoices.edit', compact('invoice', 'profile', 'clients', 'catalogItems'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return redirect()->route('invoices.show', $invoice)->with('error', __('app.finalized_invoice_cannot_be_modified'));
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'tax_inclusive' => 'boolean',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
            'payment_link' => 'nullable|url',
            'pdf_template' => ['required', Rule::in(array_keys(app(PdfService::class)->availableTemplates()))],
            'page_size' => 'required|in:a4,letter',
            'locale' => 'required|in:en,es',
            'show_signature' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_name' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update([
                'client_id' => $request->client_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'currency' => strtoupper($request->currency),
                'exchange_rate' => $request->filled('exchange_rate') ? $request->exchange_rate : 1.000000,
                'tax_inclusive' => $request->boolean('tax_inclusive', false),
                'shipping_fee_cents' => (int) round(((float)$request->shipping_fee) * 100),
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'payment_instructions' => $request->payment_instructions,
                'payment_link' => $request->payment_link,
                'pdf_template' => $request->pdf_template,
                'page_size' => $request->page_size,
                'locale' => $request->locale,
                'show_signature' => $request->has('show_signature') ? $request->boolean('show_signature') : null,
            ]);

            // Replace line items
            $invoice->items()->delete();

            foreach ($request->items as $index => $itemData) {
                $discountBps = isset($itemData['discount_percent']) ? (int) round($itemData['discount_percent'] * 100) : 0;
                $taxRateBps = isset($itemData['tax_rate_percent']) ? (int) round($itemData['tax_rate_percent'] * 100) : 0;
                $unitPriceCents = (int) round(((float)$itemData['unit_price']) * 100);

                $invoice->items()->create([
                    'sort_order' => $index,
                    'catalog_item_id' => $itemData['catalog_item_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price_cents' => $unitPriceCents,
                    'discount_bps' => $discountBps,
                    'tax_rate_bps' => $taxRateBps,
                    'tax_name' => $itemData['tax_name'] ?? null,
                ]);
            }

            $invoice->recalculateTotals();

            AuditLog::log($invoice->id, 'updated', ['total_cents' => $invoice->total_cents]);
        });

        return redirect()->route('invoices.show', $invoice)->with('success', __('app.invoice_updated'));
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return back()->with('error', __('app.finalized_invoice_cannot_be_deleted'));
        }

        $invoice->items()->delete();
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', __('app.draft_deleted'));
    }

    public function finalize(Invoice $invoice)
    {
        $invoice->finalize();
        return back()->with('success', __('app.document_finalized'));
    }

    public function void(Request $request, Invoice $invoice)
    {
        $invoice->markAsVoid($request->input('reason', __('app.manually_voided')));
        return back()->with('success', __('app.invoice_voided'));
    }

    public function duplicate(Invoice $invoice)
    {
        $clone = $invoice->duplicate();
        return redirect()->route('invoices.edit', $clone)->with('success', __('app.invoice_cloned', ['number' => $clone->invoice_number]));
    }

    public function convert(Invoice $invoice)
    {
        $newInvoice = $invoice->convertQuoteToInvoice();
        return redirect()->route('invoices.show', $newInvoice)->with('success', __('app.quote_converted', ['number' => $newInvoice->invoice_number]));
    }

    public function creditNote(Invoice $invoice)
    {
        $creditNote = $invoice->createCreditNote();
        return redirect()->route('invoices.edit', $creditNote)->with('success', __('app.credit_note_created', ['number' => $creditNote->invoice_number]));
    }

    public function pdf(Invoice $invoice, PdfService $pdfService)
    {
        $pdf = $pdfService->generate($invoice);
        $filename = $pdfService->getFilename($invoice);

        AuditLog::log($invoice->id, 'pdf_downloaded');

        return $pdf->stream($filename);
    }

    public function preview(Request $request, Invoice $invoice, PdfService $pdfService)
    {
        $template = $request->query('template', $invoice->pdf_template);
        abort_unless($pdfService->supportsTemplate($template), 404);

        // Avoid PdfService::generate()'s normal first-or-create profile fallback:
        // preview is strictly read-only, even in an incompletely initialized database.
        $profile = BusinessProfile::query()->first();
        abort_unless($profile, 404);

        $pdf = $pdfService->generate($invoice, $profile, $template);

        return $pdf->stream($pdfService->getFilename($invoice));
    }

    /**
     * PDF preview of the create form as it stands right now. Strictly read-only:
     * the invoice is built in memory and never persisted.
     */
    public function previewDraft(Request $request, PdfService $pdfService, DraftInvoiceFactory $factory)
    {
        $profile = BusinessProfile::query()->first();
        abort_unless($profile, 404);

        $clientId = $request->input('client_id');
        $client = is_numeric($clientId) ? Client::find($clientId) : null;

        $invoice = $factory->make($request->all(), $profile, $client);

        return $pdfService->generate($invoice, $profile, $request->input('pdf_template'))->stream('preview.pdf');
    }

    public function sendEmail(Request $request, Invoice $invoice, PdfService $pdfService)
    {
        $recipient = $request->input('email', $invoice->client->email);
        if (empty($recipient)) {
            return back()->with('error', __('app.client_has_no_email'));
        }

        try {
            $pdf = $pdfService->generate($invoice);
            $filename = $pdfService->getFilename($invoice);

            Mail::to($recipient)->send(new InvoiceMail($invoice, $pdf->output(), $filename));

            $invoice->update([
                'status' => $invoice->status === 'draft' ? 'sent' : $invoice->status,
                'sent_at' => now(),
            ]);

            AuditLog::log($invoice->id, 'email_sent', ['recipient' => $recipient]);

            return back()->with('success', __('app.invoice_emailed', ['email' => $recipient]));
        } catch (\Throwable $e) {
            return back()->with('error', __('app.invoice_email_failed', ['message' => $e->getMessage()]));
        }
    }
}
