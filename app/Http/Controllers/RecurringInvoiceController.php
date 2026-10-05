<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\RecurringInvoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function index()
    {
        $recurringInvoices = RecurringInvoice::with('client')
            ->orderBy('next_issue_date', 'asc')
            ->paginate(15);

        return view('recurring.index', compact('recurringInvoices'));
    }

    public function create()
    {
        $profile = BusinessProfile::current();
        $clients = Client::orderBy('name')->get();
        $catalogItems = CatalogItem::active()->orderBy('name')->get();

        return view('recurring.create', compact('profile', 'clients', 'catalogItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'currency' => 'required|string|size:3',
            'payment_terms_days' => 'required|integer|min:0',
            'auto_send' => 'boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_name' => 'nullable|string',
        ]);

        $itemsPayload = [];
        foreach ($request->items as $item) {
            $itemsPayload[] = [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price_cents' => (int) round(((float)$item['unit_price']) * 100),
                'tax_rate_bps' => isset($item['tax_rate_percent']) ? (int) round($item['tax_rate_percent'] * 100) : 0,
                'tax_name' => $item['tax_name'] ?? null,
            ];
        }

        RecurringInvoice::create([
            'client_id' => $validated['client_id'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['start_date'],
            'next_issue_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'payment_terms_days' => $validated['payment_terms_days'],
            'auto_send' => $request->boolean('auto_send', false),
            'status' => 'active',
            'template_payload' => [
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'],
                'items' => $itemsPayload,
            ],
        ]);

        return redirect()->route('recurring.index')->with('success', 'Recurring schedule created.');
    }

    public function generateNow(RecurringInvoice $recurring)
    {
        $invoice = $recurring->generateInvoice();
        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} successfully generated from recurring schedule.");
    }

    public function destroy(RecurringInvoice $recurring)
    {
        $recurring->delete();
        return redirect()->route('recurring.index')->with('success', 'Recurring schedule removed.');
    }
}
