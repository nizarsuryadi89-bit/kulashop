<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'creator'])->orderBy('expense_date', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('expense_category_id', $request->category_id);
        }
        
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $expenses = $query->paginate(20)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        
        $totalExpenses = $query->where('status', 'paid')->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'totalExpenses'));
    }

    public function create()
    {
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank_transfer,e_wallet,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,paid,cancelled',
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        $data = $request->all();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file')->store('expenses/receipts', 'public');
        }

        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Expense $expense)
    {
        if ($expense->status === 'cancelled') {
            return redirect()->route('expenses.index')->with('error', 'Data pengeluaran yang sudah dibatalkan tidak dapat diedit.');
        }
        
        $categories = ExpenseCategory::where('status', 'active')->orWhere('id', $expense->expense_category_id)->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->status === 'cancelled') {
            return redirect()->route('expenses.index')->with('error', 'Data pengeluaran yang sudah dibatalkan tidak dapat diedit.');
        }

        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank_transfer,e_wallet,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,paid,cancelled',
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        $data = $request->all();
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('receipt_file')) {
            if ($expense->receipt_file) {
                Storage::disk('public')->delete($expense->receipt_file);
            }
            $data['receipt_file'] = $request->file('receipt_file')->store('expenses/receipts', 'public');
        }

        $expense->update($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->update([
            'status' => 'cancelled',
            'updated_by' => auth()->id()
        ]);

        return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil dibatalkan.');
    }
}
