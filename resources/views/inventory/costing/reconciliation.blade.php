@extends('layouts.app')
@section('title', 'Cost Reconciliation')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Cost Reconciliation <i class="fas fa-balance-scale text-warning ms-2"></i></h4>
    </div>
    
    <div class="alert alert-info border-0 shadow-sm" style="border-radius: 12px;">
        <i class="fas fa-info-circle me-2"></i> Halaman ini membandingkan <strong>kuantitas fisik stok</strong> di gudang dengan <strong>kuantitas pada modul Costing</strong>. Perbedaan (mismatch) dapat terjadi jika ada penyesuaian stok yang tidak melibatkan nilai uang (unit cost).
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('inventory.costing.reconciliation') }}" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" placeholder="Cari nama produk / SKU..." value="{{ $search }}">
                            <button class="btn btn-primary px-3" type="submit">Cari</button>
                        </div>
                    </div>
                    @if($search)
                        <div class="col-md-auto">
                            <a href="{{ route('inventory.costing.reconciliation') }}" class="btn btn-light btn-sm"><i class="fas fa-times me-1"></i> Reset</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Produk</th>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">SKU</th>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Gudang</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Physical Stock Qty</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Cost Summary Qty</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Difference</th>
                            <th class="fw-bold text-uppercase text-center" style="font-size: 0.8rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $stock)
                        <tr class="{{ $stock->status == 'MISMATCH' ? 'bg-danger bg-opacity-10' : '' }}">
                            <td class="fw-bold text-dark">{{ $stock->product->product_name }}</td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $stock->product->sku }}</span></td>
                            <td class="text-muted"><i class="fas fa-warehouse me-1"></i> {{ $stock->warehouse->name }}</td>
                            <td class="text-end fw-bold">{{ number_format($stock->stock, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($stock->cost_qty, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $stock->difference != 0 ? 'text-danger' : 'text-success' }}">
                                {{ $stock->difference > 0 ? '+' : '' }}{{ number_format($stock->difference, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($stock->status == 'MATCH')
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> MATCH</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> MISMATCH</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-balance-scale fa-3x mb-3 text-light"></i>
                                <p class="mb-0">Tidak ada data stok yang ditemukan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $stocks->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
