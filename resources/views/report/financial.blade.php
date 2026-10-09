@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('page_title', 'Laporan Keuangan')

@section('content')
<style>
    .summary-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 1rem;
        overflow: hidden;
        border: none;
        position: relative;
    }
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    .summary-icon {
        position: absolute;
        right: -10px;
        bottom: -15px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-10deg);
        transition: transform 0.3s;
    }
    .summary-card:hover .summary-icon {
        transform: rotate(0) scale(1.1);
    }
    .bg-gradient-success { background: linear-gradient(135deg, #20c997 0%, #198754 100%); }
    .bg-gradient-danger { background: linear-gradient(135deg, #f87171 0%, #dc3545 100%); }
    .bg-gradient-primary { background: linear-gradient(135deg, #60a5fa 0%, #0d6efd 100%); }
    .bg-gradient-warning { background: linear-gradient(135deg, #fbbf24 0%, #ffc107 100%); }
    .bg-gradient-secondary { background: linear-gradient(135deg, #9ca3af 0%, #6c757d 100%); }
    .table-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .table-card .card-header {
        border-radius: 1rem 1rem 0 0;
    }
</style>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h6 class="mb-0 text-uppercase fw-bold text-muted">Filter Laporan</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('report.financial') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Bulan Penjualan</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter Laporan</button>
            </div>
        </form>

        <form id="form-sync-hpp" action="{{ route('report.financial.sync_hpp') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmSync()">
                <i class="fas fa-sync-alt me-1"></i> Sinkronisasi HPP Bulan Ini
            </button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card summary-card bg-gradient-success text-white h-100 shadow-sm">
            <div class="card-body p-4 position-relative">
                <i class="fas fa-wallet summary-icon text-white"></i>
                <div class="position-relative z-index-1">
                    <p class="mb-1 text-white-50 fw-bold small text-uppercase tracking-wide">TOTAL PENGHASILAN (NETTO)</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totals['net_revenue'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card bg-gradient-danger text-white h-100 shadow-sm">
            <div class="card-body p-4 position-relative">
                <i class="fas fa-boxes summary-icon text-white"></i>
                <div class="position-relative z-index-1">
                    <p class="mb-1 text-white-50 fw-bold small text-uppercase tracking-wide">REALIZED HPP (MODAL)</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totals['total_hpp'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card bg-gradient-primary text-white h-100 shadow-sm">
            <div class="card-body p-4 position-relative">
                <i class="fas fa-chart-line summary-icon text-white"></i>
                <div class="position-relative z-index-1">
                    <p class="mb-1 text-white-50 fw-bold small text-uppercase tracking-wide">TOTAL LABA BERSIH</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totals['net_profit'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card summary-card bg-gradient-warning text-dark h-100 shadow-sm">
            <div class="card-body p-4 position-relative">
                <i class="fas fa-ticket-alt summary-icon text-dark"></i>
                <div class="position-relative z-index-1">
                    <p class="mb-1 text-dark-50 fw-bold small text-uppercase tracking-wide">TOTAL VOUCHER PENJUAL</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totals['total_voucher_seller'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card summary-card bg-gradient-secondary text-white h-100 shadow-sm">
            <div class="card-body p-4 position-relative">
                <i class="fas fa-bullhorn summary-icon text-white"></i>
                <div class="position-relative z-index-1">
                    <p class="mb-1 text-white-50 fw-bold small text-uppercase tracking-wide">BIAYA LAYANAN PROMO XTRA</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totals['total_promo_xtra'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-table text-primary me-2"></i> Rekapitulasi per Marketplace <span class="text-muted fw-normal fs-6 ms-1">(Bulan: {{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }})</span></h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="text-muted fw-bold small text-uppercase">Marketplace</th>
                        <th class="text-center text-muted fw-bold small text-uppercase">Pesanan Selesai</th>
                        <th class="text-end text-muted fw-bold small text-uppercase">Total Omzet (Kotor)</th>
                        <th class="text-end text-muted fw-bold small text-uppercase">Total Ongkir</th>
                        <th class="text-end text-muted fw-bold small text-uppercase">Total Admin</th>
                        <th class="text-end text-muted fw-bold small text-uppercase">Pajak Aplikasi</th>
                        <th class="text-end text-success fw-bold small text-uppercase">Pendapatan Bersih</th>
                        <th class="text-end text-danger fw-bold small text-uppercase">HPP (Modal)</th>
                        <th class="text-end text-primary fw-bold small text-uppercase">Laba Bersih</th>
                        <th class="text-center text-muted fw-bold small text-uppercase" style="width:10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketplaces as $mp)
                        <tr>
                            <td>
                                <strong>{{ $mp->name }}</strong><br>
                                <small class="text-muted">Pajak: {{ number_format($mp->tax_percentage, 2, ',', '.') }}%</small>
                            </td>
                            <td class="text-center">{{ $mp->orders_count }}</td>
                            <td class="text-end">Rp {{ number_format($mp->total_gross, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($mp->total_shipping, 0, ',', '.') }} <small class="text-muted d-block" style="font-size:0.7em;">(Cashless)</small></td>
                            <td class="text-end text-danger">- Rp {{ number_format($mp->total_admin, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">- Rp {{ number_format($mp->total_tax, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format($mp->net_revenue, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-danger">- Rp {{ number_format($mp->total_hpp, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($mp->net_profit, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('order.index', ['marketplace_account_id' => $mp->id, 'month' => $month]) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail Pesanan">
                                    <i class="fas fa-search"></i> Detail
                                </a>
                                @if(str_contains(strtolower($mp->code ?? ''), 'shopee') || str_contains(strtolower($mp->name), 'shopee'))
                                <a href="{{ route('report.shopee_income', ['marketplace' => $mp->id, 'month' => $month]) }}" class="btn btn-sm btn-outline-warning mt-1" title="Rincian Shopee (Sesuai Excel)">
                                    <i class="fas fa-file-invoice-dollar"></i> Rincian Shopee
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">Belum ada data marketplace.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th class="text-end">TOTAL KESELURUHAN</th>
                        <th class="text-center">{{ $totals['orders_count'] }}</th>
                        <th class="text-end">Rp {{ number_format($totals['grand_total'], 0, ',', '.') }}</th>
                        <th class="text-end fw-bold">Rp {{ number_format($totals['shipping_fee'], 0, ',', '.') }}</th>
                        <th class="text-end text-danger">- Rp {{ number_format($totals['admin_fee'], 0, ',', '.') }}</th>
                        <th class="text-end text-danger">- Rp {{ number_format($totals['tax_amount'], 0, ',', '.') }}</th>
                        <th class="text-end fw-bold text-success fs-6">Rp {{ number_format($totals['net_revenue'], 0, ',', '.') }}</th>
                        <th class="text-end fw-bold text-danger fs-6">- Rp {{ number_format($totals['total_hpp'], 0, ',', '.') }}</th>
                        <th class="text-end fw-bold text-primary fs-6">Rp {{ number_format($totals['net_profit'], 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmSync() {
        Swal.fire({
            title: 'Sinkronisasi HPP',
            text: 'Tindakan ini akan menarik HPP terbaru dari Master Produk untuk mengisi pesanan yang HPP-nya masih kosong atau bernilai Rp 0 pada bulan ini. Lanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Sinkronisasikan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-sync-hpp').submit();
            }
        });
    }
</script>
@endpush
