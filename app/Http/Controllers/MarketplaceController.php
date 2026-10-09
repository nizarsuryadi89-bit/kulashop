<?php

namespace App\Http\Controllers;

use App\Models\Marketplace;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $marketplaces = Marketplace::withCount('accounts')->get();

        return view('marketplace.index', compact('marketplaces'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not used, using modal
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Format before validation if needed, but it's better to just force uppercase
        $request->merge([
            'code' => strtoupper($request->code),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:marketplaces,code',
            'is_active' => 'required|boolean',
        ]);

        Marketplace::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('marketplaces.index')->with('success', 'Marketplace berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $marketplace = Marketplace::with('accounts')->findOrFail($id);

        return view('marketplace.show', compact('marketplace'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Not used, using modal
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $marketplace = Marketplace::findOrFail($id);

        $request->merge([
            'code' => strtoupper($request->code),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:marketplaces,code,'.$id,
            'is_active' => 'required|boolean',
        ]);

        $marketplace->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('marketplaces.index')->with('success', 'Marketplace berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $marketplace = Marketplace::findOrFail($id);

        if ($marketplace->accounts()->exists()) {
            return redirect()->route('marketplaces.index')->with('error', 'Marketplace tidak bisa dihapus karena memiliki toko/akun terkait. Silakan ubah status menjadi Nonaktif.');
        }

        $marketplace->delete();

        return redirect()->route('marketplaces.index')->with('success', 'Marketplace berhasil dihapus.');
    }
}
