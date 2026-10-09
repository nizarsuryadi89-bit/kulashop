<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name', 'asc')->paginate(20);
        return view('expenses.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('expenses.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:expense_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        ExpenseCategory::create($request->all());

        return redirect()->route('expense-categories.index')->with('success', 'Kategori pengeluaran berhasil ditambahkan.');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('expenses.categories.edit', compact('expenseCategory'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:expense_categories,code,' . $expenseCategory->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $expenseCategory->update($request->all());

        return redirect()->route('expense-categories.index')->with('success', 'Kategori pengeluaran berhasil diperbarui.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->count() > 0) {
            $expenseCategory->update(['status' => 'inactive']);
            return redirect()->route('expense-categories.index')->with('success', 'Kategori dinonaktifkan karena sudah digunakan dalam transaksi.');
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Kategori pengeluaran berhasil dihapus.');
    }
}
