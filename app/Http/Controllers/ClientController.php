<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::search($request->search)
            ->withCount('invoices')
            ->withSum(['invoices as total_billed_cents' => function ($q) {
                $q->where('type', 'invoice')->where('status', '!=', 'void');
            }], 'total_cents')
            ->withSum(['invoices as total_balance_cents' => function ($q) {
                $q->where('type', 'invoice')->where('status', '!=', 'void');
            }], 'balance_cents')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        $profile = BusinessProfile::current();
        return view('clients.create', compact('profile'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'default_tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'default_payment_terms_days' => 'nullable|integer|min:0|max:365',
            'locale' => 'required|string|in:en,es',
        ]);

        $defaultTaxRateBps = isset($validated['default_tax_rate_percent']) 
            ? (int) round($validated['default_tax_rate_percent'] * 100) 
            : null;

        Client::create([
            'name' => $validated['name'],
            'company' => $validated['company'],
            'tax_id' => $validated['tax_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'website' => $validated['website'],
            'billing_address' => $validated['billing_address'],
            'shipping_address' => $validated['shipping_address'],
            'notes' => $validated['notes'],
            'currency' => $validated['currency'] ?: null,
            'default_tax_rate_bps' => $defaultTaxRateBps,
            'default_payment_terms_days' => $validated['default_payment_terms_days'] ?: null,
            'locale' => $validated['locale'],
        ]);

        return redirect()->route('clients.index')->with('success', __('app.client_created'));
    }

    public function show(Client $client)
    {
        $invoices = $client->invoices()->with('payments')->orderBy('created_at', 'desc')->paginate(10);
        $totalBilled = $client->invoices()->where('type', 'invoice')->where('status', '!=', 'void')->sum('total_cents');
        $totalPaid = $client->invoices()->where('type', 'invoice')->sum('amount_paid_cents');
        $balanceDue = $client->invoices()->where('type', 'invoice')->where('status', '!=', 'void')->sum('balance_cents');

        return view('clients.show', compact('client', 'invoices', 'totalBilled', 'totalPaid', 'balanceDue'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'default_tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'default_payment_terms_days' => 'nullable|integer|min:0|max:365',
            'locale' => 'required|string|in:en,es',
        ]);

        $defaultTaxRateBps = isset($validated['default_tax_rate_percent']) 
            ? (int) round($validated['default_tax_rate_percent'] * 100) 
            : null;

        $client->update([
            'name' => $validated['name'],
            'company' => $validated['company'],
            'tax_id' => $validated['tax_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'website' => $validated['website'],
            'billing_address' => $validated['billing_address'],
            'shipping_address' => $validated['shipping_address'],
            'notes' => $validated['notes'],
            'currency' => $validated['currency'] ?: null,
            'default_tax_rate_bps' => $defaultTaxRateBps,
            'default_payment_terms_days' => $validated['default_payment_terms_days'] ?: null,
            'locale' => $validated['locale'],
        ]);

        return redirect()->route('clients.show', $client)->with('success', __('app.client_updated'));
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists()) {
            return back()->with('error', __('app.client_delete_rejected'));
        }

        $client->delete();
        return redirect()->route('clients.index')->with('success', __('app.client_deleted'));
    }
}
