<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        $profile = BusinessProfile::current();
        return view('settings.index', compact('profile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'default_currency' => 'required|string|size:3',
            'default_payment_terms_days' => 'required|integer|min:0|max:365',
            'default_tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'default_tax_inclusive' => 'boolean',
            'default_notes' => 'nullable|string',
            'default_footer' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:100',
            'bank_swift' => 'nullable|string|max:50',
            'paypal_email' => 'nullable|email|max:255',
            'payment_link' => 'nullable|url|max:255',
            'invoice_prefix' => 'required|string|max:20',
            'invoice_number_digits' => 'required|integer|min:1|max:10',
            'next_invoice_number' => 'required|integer|min:1',
            'quote_prefix' => 'required|string|max:20',
            'quote_number_digits' => 'required|integer|min:1|max:10',
            'next_quote_number' => 'required|integer|min:1',
            'credit_note_prefix' => 'required|string|max:20',
            'credit_note_number_digits' => 'required|integer|min:1|max:10',
            'next_credit_note_number' => 'required|integer|min:1',
            'default_template' => 'required|in:modern,classic,minimal',
            'default_page_size' => 'required|in:a4,letter',
            'default_locale' => 'required|in:en,es',
            'timezone' => 'required|string|timezone',
        ]);

        $profile = BusinessProfile::current();

        if ($request->hasFile('logo')) {
            $logoDir = public_path('uploads/logos');
            if (!File::isDirectory($logoDir)) {
                File::makeDirectory($logoDir, 0755, true);
            }
            $filename = 'logo_' . time() . '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->move($logoDir, $filename);
            $validated['logo_path'] = 'uploads/logos/' . $filename;
        }

        $validated['default_currency'] = strtoupper($validated['default_currency']);
        $validated['default_tax_rate_bps'] = isset($validated['default_tax_rate_percent']) ? (int) round($validated['default_tax_rate_percent'] * 100) : 0;
        $validated['default_tax_inclusive'] = $request->boolean('default_tax_inclusive', false);

        unset($validated['logo'], $validated['default_tax_rate_percent']);

        $profile->update($validated);

        return redirect()->route('settings.index')->with('success', 'Business profile & preferences updated.');
    }
}
