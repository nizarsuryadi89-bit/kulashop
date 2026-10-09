<?php

namespace App\Http\Controllers;

use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use Illuminate\Http\Request;

class MarketplaceAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = MarketplaceAccount::with('marketplace')->get();
        $marketplaces = Marketplace::where('is_active', true)->get();

        return view('marketplace_account.index', compact('accounts', 'marketplaces'));
    }

    /**
     * API Endpoint to get accounts by marketplace
     */
    public function getByMarketplace($marketplaceId)
    {
        $accounts = MarketplaceAccount::where('marketplace_id', $marketplaceId)
            ->where('status', 'active')
            ->select('id', 'account_name', 'account_code')
            ->get();

        return response()->json($accounts);
    }

    public function create()
    {
        // Not used, using modal
    }

    public function store(Request $request)
    {
        $request->merge([
            'account_code' => strtoupper($request->account_code),
        ]);

        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'account_name' => 'required|string|max:100',
            'account_code' => 'required|string|max:50|unique:marketplace_accounts,account_code',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        MarketplaceAccount::create([
            'marketplace_id' => $validated['marketplace_id'],
            'account_name' => $validated['account_name'],
            'account_code' => $validated['account_code'],
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'status' => $validated['status'],
        ]);

        return redirect()->route('marketplace-accounts.index')->with('success', 'Akun toko berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $account = MarketplaceAccount::with('marketplace')
            ->withCount(['orders', 'productMarketplaces', 'importBatches'])
            ->findOrFail($id);

        return view('marketplace_account.show', compact('account'));
    }

    public function edit(string $id)
    {
        // Not used, using modal
    }

    public function update(Request $request, string $id)
    {
        $account = MarketplaceAccount::findOrFail($id);

        $request->merge([
            'account_code' => strtoupper($request->account_code),
        ]);

        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'account_name' => 'required|string|max:100',
            'account_code' => 'required|string|max:50|unique:marketplace_accounts,account_code,'.$id,
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $account->update([
            'marketplace_id' => $validated['marketplace_id'],
            'account_name' => $validated['account_name'],
            'account_code' => $validated['account_code'],
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'status' => $validated['status'],
        ]);

        return redirect()->route('marketplace-accounts.index')->with('success', 'Akun toko berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $account = MarketplaceAccount::findOrFail($id);

        $hasOrders = $account->orders()->exists();
        $hasProducts = $account->productMarketplaces()->exists();
        $hasImports = $account->importBatches()->exists();

        if ($hasOrders || $hasProducts || $hasImports) {
            return redirect()->route('marketplace-accounts.index')->with('error', 'Toko tidak bisa dihapus karena memiliki transaksi/data terkait. Silakan ubah status menjadi Nonaktif.');
        }

        $account->delete();

        return redirect()->route('marketplace-accounts.index')->with('success', 'Akun toko berhasil dihapus.');
    }
}
