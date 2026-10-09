<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount('stocks')
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('warehouse.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        // If this is the first warehouse, make it default automatically
        if (Warehouse::count() === 0) {
            $isDefault = true;
        }

        Warehouse::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'is_default' => $isDefault,
            'status' => $validated['status'],
        ]);

        return redirect()->route('warehouses.index')->with('success', 'Gudang baru berhasil ditambahkan.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,'.$warehouse->id,
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'is_default' => $isDefault,
            'status' => $validated['status'],
        ]);

        return redirect()->route('warehouses.index')->with('success', 'Data gudang berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return back()->with('error', 'Gudang default tidak dapat dihapus. Silakan tentukan gudang default lain terlebih dahulu.');
        }

        if ($warehouse->stockMovements()->exists()) {
            return back()->with('error', 'Gudang tidak dapat dihapus karena sudah memiliki histori transaksi stok. Silakan ubah status menjadi Inactive.');
        }

        $hasStock = $warehouse->stocks()->where('qty', '>', 0)->exists();
        if ($hasStock) {
            return back()->with('error', 'Gudang tidak dapat dihapus karena masih memiliki produk dengan saldo stok.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil dihapus.');
    }

    public function setDefault(Warehouse $warehouse)
    {
        Warehouse::where('is_default', true)->update(['is_default' => false]);
        $warehouse->update(['is_default' => true, 'status' => 'active']);

        return back()->with('success', "Gudang {$warehouse->name} berhasil dijadikan sebagai Gudang Utama (Default).");
    }
}
