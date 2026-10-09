@extends('layouts.app')

@section('title', 'Profit & Margin (Shopee)')

@section('content')
<style>
    .summary-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 1rem;
        overflow: hidden;
    }
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
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
    .table-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }
    .table-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        border-radius: 1rem 1rem 0 0;
        padding: 1.25rem 1.5rem;
    }
</style>

<div class="container-fluid">

    <div class="col-12">
        <h1 class="h3 mb-3 text-gray-800">
            Laporan Laba & Margin 
            <span class="badge bg-info text-white fs-6 ms-2 align-middle" style="font-size: 0.6em;">Berbasis Tanggal Order</span>
        </h1>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filter Analisis</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('report.profit-margin') }}" id="profitFilterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Periode Order</label>
                        <select name="period" class="form-control" onchange="toggleCustomDate(this.value)">
                            <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="yesterday" {{ $period == 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                            <option value="7_days" {{ $period == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                            <option value="30_days" {{ $period == '30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                            <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Custom Tanggal</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 custom-date" style="display: {{ $period == 'custom' ? 'block' : 'none' }}">
                        <label>Mulai Tgl Order</label>
                        <input type="date" name="start_date" id="pm_start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2 mb-3 custom-date" style="display: {{ $period == 'custom' ? 'block' : 'none' }}">
                        <label>Sampai Tgl Order</label>
                        <input type="date" name="end_date" id="pm_end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 mb-3 custom-date" style="display: {{ $period == 'custom' ? 'block' : 'none' }}">
                        <label class="text-primary fw-bold"><i class="far fa-calendar-alt"></i> Set Bulan</label>
                        <input type="month" class="form-control border-primary" onchange="setMonth(this.value, 'pm_start_date', 'pm_end_date')" title="Pilih bulan untuk otomatis mengisi tanggal mulai dan sampai">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Status Order</label>
                        <select name="order_status" class="form-control">
                            <option value="all" {{ request('order_status') == 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="COMPLETED" {{ request('order_status', 'COMPLETED') == 'COMPLETED' ? 'selected' : '' }}>Selesai</option>
                            <option value="CANCELLED" {{ request('order_status') == 'CANCELLED' ? 'selected' : '' }}>Dibatalkan</option>
                            <option value="RETURNED" {{ request('order_status') == 'RETURNED' ? 'selected' : '' }}>Dikembalikan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Pencarian SKU / Produk</label>
                        <input type="text" name="sku" class="form-control" placeholder="Ketik SKU..." value="{{ request('sku') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-right">
                        <a href="{{ route('report.profit-margin') }}" class="btn btn-default"><i class="fas fa-sync"></i> Reset</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Terapkan Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($summary['incomplete_orders_count'] > 0)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-3 me-3 text-warning"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Sebagian transaksi belum memiliki data HPP!</h6>
                <p class="mb-0 small">
                    Profit belum dapat dihitung secara lengkap. Ada <strong>{{ number_format($summary['incomplete_orders_count']) }} transaksi</strong> yang membutuhkan HPP.
                    <a href="#" class="fw-bold ms-1 text-decoration-none" data-bs-toggle="modal" data-bs-target="#missingHppModal">
                        [Lihat Transaksi] <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </p>
            </div>
        </div>
    @endif

    <!-- Order Status Overview -->
    <div class="row mb-4 g-3">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-shopping-cart fs-5"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Total Pesanan</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($summary['orders_count'], 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-check-circle fs-5"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Laba Selesai</p>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($summary['complete_orders_count'], 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-clock fs-5"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Menunggu Pendapatan</p>
                        <h4 class="mb-0 fw-bold text-warning">{{ number_format($summary['waiting_income_count'], 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex align-items-center cursor-pointer" @if($summary['incomplete_orders_count'] > 0) data-bs-toggle="modal" data-bs-target="#missingHppModal" @endif>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Laba Belum Lengkap</p>
                        <h4 class="mb-0 fw-bold text-danger">{{ number_format($summary['incomplete_orders_count'], 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-dark bg-opacity-10 text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-question-circle fs-5"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Pendapatan Tidak Cocok <span class="badge bg-secondary ms-1">{{ $summary['unmatched_income_count'] }}</span></p>
                        <h5 class="mb-0 fw-bold">Rp {{ number_format($summary['unmatched_income'], 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards (Hanya dari Profit Complete) -->
    <div class="row mb-4 g-4">
        <div class="col-md-4">
            <div class="card bg-gradient-info text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #17a2b8, #1fc8e3);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Total Pembayaran</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-wallet text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">Rp {{ number_format($summary['total_payment'], 0, ',', '.') }}</h3>
                    <small class="text-white-50">Yang dibayar oleh Customer</small>
                </div>
                <i class="fas fa-wallet summary-icon"></i>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-gradient-primary text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #4e73df, #224abe);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Penghasilan Shopee (Net Revenue)</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-store text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</h3>
                    <small class="text-white-50">Setelah potongan/fee Marketplace</small>
                </div>
                <i class="fas fa-store summary-icon"></i>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-gradient-secondary text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #6c757d, #5a6268);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Total HPP (COGS)</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-box-open text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}</h3>
                    <small class="text-white-50">Modal Pokok Penjualan</small>
                </div>
                <i class="fas fa-box-open summary-icon"></i>
            </div>
        </div>
    </div>
    
    <div class="row mb-5 g-4">
        <div class="col-md-6">
            <div class="card bg-gradient-success text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #1cc88a, #13855c);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Laba Kotor (Gross Profit)</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1">Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}</h2>
                    <small class="text-white-50">Penghasilan Shopee - Total HPP</small>
                </div>
                <i class="fas fa-chart-line summary-icon"></i>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, {{ ($summary['gross_margin'] !== null && $summary['gross_margin'] >= 20) ? '#1cc88a, #13855c' : (($summary['gross_margin'] !== null && $summary['gross_margin'] > 0) ? '#f6c23e, #dda20a' : '#e74a3b, #be2617') }});">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Margin Laba Kotor</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-percentage text-white"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1">
                        @if(is_null($summary['gross_margin']))
                            N/A
                        @else
                            {{ number_format($summary['gross_margin'], 2, ',', '.') }}%
                        @endif
                    </h2>
                    <small class="text-white-50 text-dark-50">(Gross Profit / Penghasilan) x 100%</small>
                </div>
                <i class="fas fa-percentage summary-icon"></i>
            </div>
        </div>
    </div>

    <!-- KPI Cards (Laba Bersih / Net Profit) -->
    <div class="row mb-5 g-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100 border-0 shadow-sm summary-card position-relative">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Biaya Operasional</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-file-invoice-dollar text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">Rp {{ number_format($summary['total_operating_expenses'] ?? 0, 0, ',', '.') }}</h3>
                    <small class="text-white-50">Pengeluaran Bisnis (Iklan, Gaji, dll)</small>
                </div>
                <i class="fas fa-receipt summary-icon"></i>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-primary text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Laba Bersih (Net Profit)</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-wallet text-white"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1">Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}</h2>
                    <small class="text-white-50">Gross Profit - Biaya Operasional</small>
                </div>
                <i class="fas fa-piggy-bank summary-icon"></i>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, {{ ($summary['net_margin'] !== null && $summary['net_margin'] >= 20) ? '#1cc88a, #13855c' : (($summary['net_margin'] !== null && $summary['net_margin'] > 0) ? '#f6c23e, #dda20a' : '#e74a3b, #be2617') }});">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Margin Laba Bersih</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-percentage text-white"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1">
                        @if(is_null($summary['net_margin'] ?? null))
                            N/A
                        @else
                            {{ number_format($summary['net_margin'], 2, ',', '.') }}%
                        @endif
                    </h2>
                    <small class="text-white-50">(Net Profit / Penghasilan) x 100%</small>
                </div>
                <i class="fas fa-percentage summary-icon"></i>
            </div>
        </div>
    </div>

    <!-- Product Tables -->
    <div class="row">
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-success"><i class="fas fa-arrow-up"></i> Top 10 Produk Paling Menguntungkan</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Qty</th>
                                    <th class="text-end">Laba Kotor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(collect($productMetrics['by_profit'])->take(5) as $p)
                                <tr>
                                    <td>
                                        <strong>{{ $p['sku'] }}</strong><br>
                                        <small class="text-muted">{{ Str::limit($p['name'], 30) }}</small>
                                    </td>
                                    <td>{{ $p['qty'] }}</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($p['gross_profit'], 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data profit produk</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-arrow-down"></i> Top 10 Produk Margin Terendah</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th class="text-end">Alokasi HPP</th>
                                    <th class="text-end">Alokasi Income</th>
                                    <th class="text-end">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(collect($productMetrics['by_margin'])->sortBy('margin')->take(5) as $p)
                                <tr>
                                    <td>
                                        <strong>{{ $p['sku'] }}</strong><br>
                                        <small class="text-muted">{{ Str::limit($p['name'], 20) }}</small>
                                    </td>
                                    <td class="text-end text-danger">Rp {{ number_format($p['hpp'], 0, ',', '.') }}</td>
                                    <td class="text-end text-primary">Rp {{ number_format($p['income'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ ($p['margin'] !== null && $p['margin'] < 10) ? 'text-danger' : 'text-warning' }}">
                                        @if(is_null($p['margin']))
                                            N/A
                                        @else
                                            {{ number_format($p['margin'], 2, ',', '.') }}%
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data profit produk</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Profit Trend Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line text-primary"></i> Gross Profit Trend</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="profitTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Breakdown -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Detail Biaya Marketplace & Potongan</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Admin Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['admin_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Service Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['service_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Transaction Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['transaction_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Order Processing Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['order_processing_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Promotion Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['promotion_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Affiliate Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['affiliate_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Other Fee</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['other_fee']), 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-muted small">Tax / Refund</div>
                            <h5 class="mb-0 text-danger">-Rp {{ number_format(abs($summary['fees_breakdown']['tax_amount'] + $summary['fees_breakdown']['refund_amount']), 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products by Gross Profit -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-3"><h6 class="mb-0 fw-bold text-primary">
                    <h6 class="mb-0"><i class="fas fa-trophy"></i> Top Products by Gross Profit</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Income</th>
                                    <th class="text-right">HPP</th>
                                    <th class="text-right">Gross Profit</th>
                                    <th class="text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productMetrics['by_profit'] as $prod)
                                <tr>
                                    <td><code>{{ $prod['sku'] }}</code></td>
                                    <td>{{ $prod['name'] }}</td>
                                    <td class="text-right">{{ number_format($prod['qty'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($prod['income'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($prod['hpp'], 0, ',', '.') }}</td>
                                    <td class="text-right text-success fw-bold">Rp {{ number_format($prod['gross_profit'], 0, ',', '.') }}</td>
                                    <td class="text-right text-info">
                                        @if($prod['margin'] !== null)
                                            {{ number_format($prod['margin'], 2, ',', '.') }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">Tidak ada data produk yang memadai.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products by Gross Margin -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-percentage"></i> Products by Gross Margin</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Income</th>
                                    <th class="text-right">HPP</th>
                                    <th class="text-right">Gross Profit</th>
                                    <th class="text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productMetrics['by_margin'] as $prod)
                                <tr>
                                    <td><code>{{ $prod['sku'] }}</code></td>
                                    <td>{{ $prod['name'] }}</td>
                                    <td class="text-right">{{ number_format($prod['qty'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($prod['income'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($prod['hpp'], 0, ',', '.') }}</td>
                                    <td class="text-right text-success">Rp {{ number_format($prod['gross_profit'], 0, ',', '.') }}</td>
                                    <td class="text-right text-info fw-bold">
                                        @if($prod['margin'] !== null)
                                            {{ number_format($prod['margin'], 2, ',', '.') }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">Tidak ada data produk yang memadai.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Profit By City -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Top Profit By City</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kota</th>
                                    <th class="text-right">Total Order</th>
                                    <th class="text-right">Income</th>
                                    <th class="text-right">HPP</th>
                                    <th class="text-right">Gross Profit</th>
                                    <th class="text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(array_slice($summary['city_profits'], 0, 50) as $city)
                                <tr>
                                    <td class="fw-bold">{{ $city['city'] }}</td>
                                    <td class="text-right">{{ number_format($city['order_count'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($city['income'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($city['hpp'], 0, ',', '.') }}</td>
                                    <td class="text-right text-success">Rp {{ number_format($city['gross_profit'], 0, ',', '.') }}</td>
                                    <td class="text-right text-info fw-bold">
                                        @if($city['margin'] !== null)
                                            {{ number_format($city['margin'], 2, ',', '.') }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">Data kota tidak tersedia.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Profit By Customer -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-users"></i> Top Profit By Customer</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-right">Total Order</th>
                                    <th class="text-right">Income</th>
                                    <th class="text-right">HPP</th>
                                    <th class="text-right">Gross Profit</th>
                                    <th class="text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(array_slice($summary['customer_profits'], 0, 50) as $cust)
                                <tr>
                                    <td class="fw-bold">{{ $cust['customer'] }}</td>
                                    <td class="text-right">{{ number_format($cust['order_count'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($cust['income'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($cust['hpp'], 0, ',', '.') }}</td>
                                    <td class="text-right text-success">Rp {{ number_format($cust['gross_profit'], 0, ',', '.') }}</td>
                                    <td class="text-right text-info fw-bold">
                                        @if($cust['margin'] !== null)
                                            {{ number_format($cust['margin'], 2, ',', '.') }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">Data customer tidak tersedia.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Missing HPP -->
<div class="modal fade" id="missingHppModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Daftar Order dengan HPP Incomplete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingHppOrders as $ord)
                            <tr>
                                <td>{{ $ord->external_order_id }}</td>
                                <td>{{ $ord->order_date->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('order.show', $ord->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">Lihat Order</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function setMonth(monthStr, startId, endId) {
        if(!monthStr) return;
        let parts = monthStr.split('-');
        let year = parts[0];
        let month = parts[1];
        
        let startDate = monthStr + '-01';
        let lastDay = new Date(year, month, 0).getDate();
        let endDate = monthStr + '-' + lastDay;
        
        document.getElementById(startId).value = startDate;
        document.getElementById(endId).value = endDate;
    }
</script>
@endpush
@endsection

@push('scripts')
<script>
    function toggleCustomDate(val) {
        const customDates = document.querySelectorAll('.custom-date');
        if (val === 'custom') {
            customDates.forEach(el => el.style.display = 'block');
        } else {
            customDates.forEach(el => el.style.display = 'none');
        }
    }

    // Profit Trend Chart
    document.addEventListener("DOMContentLoaded", function() {
        const trendData = @json($summary['trend']);
        
        if (trendData.length > 0) {
            const labels = trendData.map(item => item.date);
            const dataProfit = trendData.map(item => item.gross_profit);
            const dataIncome = trendData.map(item => item.income);
            const dataHpp = trendData.map(item => item.hpp);

            const ctx = document.getElementById('profitTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: dataIncome,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#007bff'
                        },
                        {
                            label: 'HPP',
                            data: dataHpp,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#dc3545'
                        },
                        {
                            label: 'Gross Profit',
                            data: dataProfit,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#28a745'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return new Intl.NumberFormat('id-ID', { notation: "compact", compactDisplay: "short" }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
