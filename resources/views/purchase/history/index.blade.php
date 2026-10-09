@extends('layouts.app')

@section('title', 'Histori Harga Beli')
@section('page_title', 'Histori Harga Beli')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-bold">Histori Harga Pembelian Produk</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('purchase.history') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari nama produk, SKU, atau No GR..." value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Pembelian</th>
                        <th>Produk</th>
                        <th>Referensi GR</th>
                        <th>Supplier</th>
                        <th>Qty Beli</th>
                        <th>Harga Satuan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $history)
                    <tr>
                        <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                        <td class="fw-semibold">
                            {{ $history->product->product_name }}<br>
                            <small class="text-muted">{{ $history->product->sku }}</small>
                        </td>
                        <td>
                            @if($history->goodsReceipt)
                                <a href="{{ route('purchase.receipts.show', $history->goods_receipt_id) }}">
                                    {{ $history->goodsReceipt->receipt_number }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $history->goodsReceipt->supplier->name ?? '-' }}</td>
                        <td>{{ number_format($history->qty, 2, ',', '.') }}</td>
                        <td class="fw-bold text-success">Rp {{ number_format($history->purchase_price, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada histori harga beli.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $histories->links() }}
        </div>
    </div>
</div>
@endsection
