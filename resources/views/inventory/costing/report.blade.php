@extends('layouts.app')
@section('title', 'Product Cost Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Product Cost Report <i class="fas fa-chart-line text-success ms-2"></i></h4>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('inventory.costing.report') }}" class="mb-4">
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
                            <a href="{{ route('inventory.costing.report') }}" class="btn btn-light btn-sm"><i class="fas fa-times me-1"></i> Reset</a>
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
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Current Cost Qty</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Average Cost</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Last Purchase Price</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Cost Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($costs as $cost)
                        <tr>
                            <td class="fw-bold text-dark">{{ $cost->product->product_name }}</td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $cost->product->sku }}</span></td>
                            <td class="text-muted"><i class="fas fa-warehouse me-1"></i> {{ $cost->warehouse->name }}</td>
                            <td class="text-end">{{ number_format($cost->quantity, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($cost->average_cost, 2, ',', '.') }}</td>
                            <td class="text-end text-muted">
                                @if($cost->last_purchase_price > 0)
                                    Rp {{ number_format($cost->last_purchase_price, 2, ',', '.') }}
                                    <div class="small" style="font-size: 0.7em;">{{ $cost->last_purchase_date->format('d M Y') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cost->cost_increase > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fas fa-arrow-up me-1"></i> +{{ number_format($cost->cost_increase, 2) }}%</span>
                                    <div class="small text-muted mt-1" style="font-size:0.7em">Beli terakhir lebih mahal</div>
                                @elseif($cost->cost_increase < 0)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-arrow-down me-1"></i> {{ number_format($cost->cost_increase, 2) }}%</span>
                                    <div class="small text-muted mt-1" style="font-size:0.7em">Beli terakhir lebih murah</div>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">0%</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-chart-bar fa-3x mb-3 text-light"></i>
                                <p class="mb-0">Tidak ada data costing yang ditemukan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $costs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
