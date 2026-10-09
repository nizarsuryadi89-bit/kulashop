@extends('layouts.app')

@section('title', 'Peringatan Stok Menipis & Habis')
@section('page_title', 'Stok Menipis & Habis')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Peringatan Stok Minimum</h1>
            <p class="text-muted small mb-0">Daftar produk dengan persediaan kritis yang memerlukan restock pengadaan segera</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Ringkasan
            </a>
            <a href="{{ route('inventory.stock-in') }}" class="btn btn-success">
                <i class="fas fa-plus-circle me-1"></i> Input Stok Masuk
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-danger">
            <i class="fas fa-exclamation-triangle me-1"></i> Produk Di Bawah Batas Minimum ({{ $products->total() }})
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>SKU / Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Stok Fisik Saat Ini</th>
                    <th class="text-end">Batas Minimum</th>
                    <th class="text-end">Kebutuhan Restock</th>
                    <th class="text-center">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $prod)
                    @php
                        $isOutOfStock = $prod->stock <= 0;
                        $shortage = max(0, $prod->minimum_stock - $prod->stock);
                    @endphp
                    <tr>
                        <td>
                            <code class="fw-bold text-dark">{{ $prod->sku }}</code>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $prod->product_name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $prod->category->name ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            @if($isOutOfStock)
                                <span class="badge bg-danger px-2 py-1">
                                    <i class="fas fa-times-circle me-1"></i> Habis
                                </span>
                            @else
                                <span class="badge bg-warning text-dark px-2 py-1">
                                    <i class="fas fa-exclamation-circle me-1"></i> Menipis
                                </span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $isOutOfStock ? 'text-danger' : 'text-warning' }}">
                            {{ number_format($prod->stock) }} pcs
                        </td>
                        <td class="text-end text-muted">{{ number_format($prod->minimum_stock) }} pcs</td>
                        <td class="text-end fw-bold text-primary">
                            +{{ number_format($shortage) }} pcs
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('inventory.stock-in') }}" class="btn btn-outline-success" title="Restock Masuk">
                                    <i class="fas fa-arrow-circle-down me-1"></i> Restock
                                </a>
                                <a href="{{ route('inventory.stock-card', $prod->id) }}" class="btn btn-outline-primary" title="Kartu Stok">
                                    <i class="fas fa-history"></i>
                                </a>
                                <a href="{{ route('inventory.adjustment', ['product_id' => $prod->id]) }}" class="btn btn-outline-warning text-dark" title="Stock Opname">
                                    <i class="fas fa-balance-scale"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success d-block"></i>
                            Kabar baik! Seluruh produk memiliki stok yang aman dan berada di atas batas minimum.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
