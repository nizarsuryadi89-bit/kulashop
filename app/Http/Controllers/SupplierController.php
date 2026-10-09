<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $suppliers = Supplier::when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%");
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(15);

        return view('purchase.suppliers.index', compact('suppliers', 'search', 'status'));
    }

    public function create()
    {
        return view('purchase.suppliers.form', ['supplier' => new Supplier()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_code' => 'required|string|unique:suppliers,supplier_code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Supplier::create($request->all());

        return redirect()->route('purchase.suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('purchase.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'supplier_code' => 'required|string|unique:suppliers,supplier_code,' . $supplier->id,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $supplier->update($request->all());

        return redirect()->route('purchase.suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }
}
