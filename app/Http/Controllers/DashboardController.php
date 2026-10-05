<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $profile = BusinessProfile::current();
        $currency = $profile->default_currency;

        // Auto-check for overdue invoices: any finalized invoice with balance > 0 and due_date < today
        Invoice::where('status', 'sent')
            ->where('due_date', '<', now()->toDateString())
            ->where('balance_cents', '>', 0)
            ->update(['status' => 'overdue']);

        // Aggregate statistics
        $invoicesQuery = Invoice::where('type', 'invoice')->where('status', '!=', 'void');

        $totalInvoicedCents = (int) (clone $invoicesQuery)->sum('total_cents');
        $totalPaidCents = (int) Payment::sum('amount_cents');
        $outstandingCents = (int) (clone $invoicesQuery)->whereIn('status', ['draft', 'sent', 'partially_paid', 'overdue'])->sum('balance_cents');
        $overdueCents = (int) (clone $invoicesQuery)->where('status', 'overdue')->sum('balance_cents');

        // Recent Invoices Query with filters
        $recentInvoicesQuery = Invoice::with('client')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $recentInvoicesQuery->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $recentInvoicesQuery->where('client_id', $request->client_id);
        }
        if ($request->filled('type')) {
            $recentInvoicesQuery->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $recentInvoicesQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
        }

        $recentInvoices = $recentInvoicesQuery->paginate(10)->withQueryString();

        // 12-month Revenue Chart Data
        $chartLabels = [];
        $invoicedData = [];
        $collectedData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $chartLabels[] = $date->format('M Y');

            $monthInvoiced = (int) Invoice::where('type', 'invoice')
                ->where('status', '!=', 'void')
                ->whereYear('issue_date', $date->year)
                ->whereMonth('issue_date', $date->month)
                ->sum('total_cents') / 100;

            $monthCollected = (int) Payment::whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount_cents') / 100;

            $invoicedData[] = $monthInvoiced;
            $collectedData[] = $monthCollected;
        }

        // Top 5 Clients
        $topClients = Client::withSum(['invoices as total_billed_cents' => function ($q) {
            $q->where('type', 'invoice')->where('status', '!=', 'void');
        }], 'total_cents')
        ->orderByDesc('total_billed_cents')
        ->limit(5)
        ->get();

        $allClients = Client::orderBy('name')->get();

        return view('dashboard.index', compact(
            'profile',
            'currency',
            'totalInvoicedCents',
            'totalPaidCents',
            'outstandingCents',
            'overdueCents',
            'recentInvoices',
            'chartLabels',
            'invoicedData',
            'collectedData',
            'topClients',
            'allClients'
        ));
    }
}
