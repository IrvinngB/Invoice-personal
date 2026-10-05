<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use Illuminate\Http\Request;

class CatalogItemController extends Controller
{
    public function index()
    {
        $items = CatalogItem::orderBy('name')->paginate(20);
        return view('catalog.index', compact('items'));
    }

    public function create()
    {
        return view('catalog.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        CatalogItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit' => $validated['unit'],
            'unit_price_cents' => (int) round($validated['unit_price'] * 100),
            'tax_rate_bps' => isset($validated['tax_rate_percent']) ? (int) round($validated['tax_rate_percent'] * 100) : 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('catalog.index')->with('success', 'Catalog item added.');
    }

    public function edit(CatalogItem $catalog)
    {
        return view('catalog.edit', ['item' => $catalog]);
    }

    public function update(Request $request, CatalogItem $catalog)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $catalog->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit' => $validated['unit'],
            'unit_price_cents' => (int) round($validated['unit_price'] * 100),
            'tax_rate_bps' => isset($validated['tax_rate_percent']) ? (int) round($validated['tax_rate_percent'] * 100) : 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('catalog.index')->with('success', 'Catalog item updated.');
    }

    public function destroy(CatalogItem $catalog)
    {
        $catalog->delete();
        return redirect()->route('catalog.index')->with('success', 'Catalog item removed.');
    }
}
