<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchasePriceHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PurchaseHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $histories = ProductPurchasePriceHistory::with(['product', 'goodsReceipt.supplier'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('product', function ($sq) use ($search) {
                    $sq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })->orWhereHas('goodsReceipt', function ($sq) use ($search) {
                    $sq->where('receipt_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('purchase.history.index', compact('histories', 'search'));
    }
}
