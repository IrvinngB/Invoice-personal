<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use App\Services\InvoiceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Download a clean SQLite database snapshot.
     */
    public function downloadSqlite()
    {
        $dbPath = database_path('database.sqlite');
        if (!file_exists($dbPath)) {
            return back()->with('error', 'SQLite database file not found.');
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupPath = storage_path("app/backup_{$timestamp}.sqlite");

        // Safely snapshot using SQLite VACUUM INTO if possible, else copy
        try {
            DB::statement("VACUUM INTO '{$backupPath}'");
        } catch (\Throwable $e) {
            File::copy($dbPath, $backupPath);
        }

        return response()->download($backupPath, "invoices_backup_{$timestamp}.sqlite")->deleteFileAfterSend(true);
    }

    /**
     * Restore database from uploaded SQLite file.
     */
    public function restoreSqlite(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');
        $dbPath = database_path('database.sqlite');

        // Backup current database before replacing
        if (file_exists($dbPath)) {
            File::copy($dbPath, database_path('database_pre_restore_' . time() . '.sqlite'));
        }

        $file->move(database_path(), 'database.sqlite');

        return redirect()->route('settings.index')->with('success', 'Database restored successfully.');
    }

    /**
     * Export all data as JSON.
     */
    public function exportJson()
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'business_profile' => BusinessProfile::first(),
            'clients' => Client::with('invoices.items', 'invoices.payments', 'recurringInvoices')->get(),
            'catalog_items' => CatalogItem::all(),
            'invoices' => Invoice::with('items', 'payments', 'auditLogs')->get(),
            'payments' => Payment::all(),
            'recurring_invoices' => RecurringInvoice::all(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = now()->format('Y-m-d_His');

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"invoice_data_export_{$timestamp}.json\"",
        ]);
    }

    /**
     * Export invoices as CSV for accountant.
     */
    public function exportInvoicesCsv(Request $request)
    {
        $query = Invoice::with(['client', 'payments'])->orderBy('issue_date', 'asc');

        if ($request->filled('from_date')) {
            $query->where('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('issue_date', '<=', $request->to_date);
        }

        $invoices = $query->get();

        $response = new StreamedResponse(function () use ($invoices) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Invoice Number',
                'Type',
                'Status',
                'Client Name',
                'Client Tax ID',
                'Issue Date',
                'Due Date',
                'Currency',
                'Exchange Rate',
                'Subtotal (Cents)',
                'Subtotal Display',
                'Discount (Cents)',
                'Discount Display',
                'Tax Total (Cents)',
                'Tax Display',
                'Shipping/Fees (Cents)',
                'Total (Cents)',
                'Total Display',
                'Amount Paid (Cents)',
                'Balance Due (Cents)',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->invoice_number,
                    $inv->type,
                    $inv->status,
                    $inv->client?->name,
                    $inv->client?->tax_id,
                    $inv->issue_date->format('Y-m-d'),
                    $inv->due_date->format('Y-m-d'),
                    $inv->currency,
                    $inv->exchange_rate,
                    $inv->subtotal_cents,
                    number_format($inv->subtotal_cents / 100, 2),
                    $inv->discount_total_cents,
                    number_format($inv->discount_total_cents / 100, 2),
                    $inv->tax_total_cents,
                    number_format($inv->tax_total_cents / 100, 2),
                    $inv->shipping_fee_cents,
                    $inv->total_cents,
                    number_format($inv->total_cents / 100, 2),
                    $inv->amount_paid_cents,
                    $inv->balance_cents,
                ]);
            }

            fclose($handle);
        });

        $timestamp = now()->format('Y-m-d');
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"invoices_report_{$timestamp}.csv\"");

        return $response;
    }
}
