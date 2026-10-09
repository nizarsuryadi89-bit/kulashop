@extends('layouts.app')

@section('title', 'Laporan Tahunan ' . $selectedYear)
@section('page_title', 'Laporan Tahunan')

@section('content')
<style>
    .kpi-card {
        border: none;
        border-radius: 1.25rem;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
        position: relative;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.12) !important;
    }
    .kpi-icon {
        position: absolute; right: -8px; bottom: -16px;
        font-size: 5.5rem; opacity: .13; transform: rotate(-8deg);
        transition: transform .3s;
    }
    .kpi-card:hover .kpi-icon { transform: rotate(0) scale(1.08); }
    .bg-gradient-success  { background: linear-gradient(135deg,#20c997 0%,#198754 100%); }
    .bg-gradient-primary  { background: linear-gradient(135deg,#60a5fa 0%,#0d6efd 100%); }
    .bg-gradient-danger   { background: linear-gradient(135deg,#f87171 0%,#dc3545 100%); }
    .bg-gradient-warning  { background: linear-gradient(135deg,#fbbf24 0%,#fd7e14 100%); }
    .bg-gradient-purple   { background: linear-gradient(135deg,#c084fc 0%,#7c3aed 100%); }
    .section-card {
        border: none; border-radius: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .section-card .card-header {
        border-radius: 1.25rem 1.25rem 0 0;
        background: #f8f9fa; border-bottom: 1px solid #e9ecef;
    }
    .profit-positive { color: #198754; font-weight: 600; }
    .profit-negative { color: #dc3545; font-weight: 600; }
    .month-row:hover { background-color: #f0f9ff !important; }
    .chart-container { position: relative; height: 320px; }
    .year-badge {
        display: inline-block; padding: 0.3rem 1rem;
        border-radius: 2rem; font-weight: 700; font-size: 1.1rem;
    }
</style>

{{-- Filter Tahun --}}
<div class="card section-card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('report.annual') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label fw-semibold mb-0">Tahun:</label>
            </div>
            <div class="col-auto">
                <select name="year" id="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                    {{-- Jika tahun saat ini belum ada datanya, tetap tampilkan --}}
                    @if(!$availableYears->contains($selectedYear))
                        <option value="{{ $selectedYear }}" selected>{{ $selectedYear }}</option>
                    @endif
                </select>
            </div>
            <div class="col-auto">
                <span class="year-badge bg-primary text-white">
                    <i class="fas fa-calendar-alt me-1"></i> Tahun {{ $selectedYear }}
                </span>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card kpi-card bg-gradient-primary text-white shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-white-50 small mb-1 text-uppercase fw-semibold">Total Order</p>
                <h4 class="fw-bold mb-0">{{ number_format($grandTotals['orders_count']) }}</h4>
                <small class="text-white-50">order selesai</small>
                <i class="fas fa-shopping-bag kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card bg-gradient-success text-white shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-white-50 small mb-1 text-uppercase fw-semibold">Pendapatan Bersih</p>
                <h4 class="fw-bold mb-0">Rp {{ number_format($grandTotals['net_revenue'], 0, ',', '.') }}</h4>
                <small class="text-white-50">setelah biaya platform</small>
                <i class="fas fa-money-bill-wave kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card bg-gradient-danger text-white shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-white-50 small mb-1 text-uppercase fw-semibold">Total HPP / Modal</p>
                <h4 class="fw-bold mb-0">Rp {{ number_format($grandTotals['total_hpp'], 0, ',', '.') }}</h4>
                <small class="text-white-50">harga pokok penjualan</small>
                <i class="fas fa-box kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card {{ $grandTotals['net_profit'] >= 0 ? 'bg-gradient-purple' : 'bg-gradient-warning' }} text-white shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-white-50 small mb-1 text-uppercase fw-semibold">Laba Bersih</p>
                <h4 class="fw-bold mb-0">Rp {{ number_format($grandTotals['net_profit'], 0, ',', '.') }}</h4>
                <small class="text-white-50">pendapatan - HPP</small>
                <i class="fas fa-chart-line kpi-icon"></i>
            </div>
        </div>
    </div>
</div>

{{-- Grafik Trend --}}
<div class="card section-card mb-4">
    <div class="card-header d-flex align-items-center gap-2 py-3">
        <i class="fas fa-chart-area text-primary"></i>
        <span class="fw-bold">Trend Bulanan {{ $selectedYear }}</span>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="annualChart"></canvas>
        </div>
    </div>
</div>

{{-- Tabel Per Bulan --}}
<div class="card section-card mb-4">
    <div class="card-header d-flex align-items-center gap-2 py-3">
        <i class="fas fa-table text-primary"></i>
        <span class="fw-bold">Rincian Per Bulan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Bulan</th>
                        <th class="text-center">Jml Order</th>
                        <th class="text-end">Pendapatan Kotor</th>
                        <th class="text-end">Pendapatan Bersih</th>
                        <th class="text-end">HPP / Modal</th>
                        <th class="text-end pe-4">Laba Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalOrdersTbl = 0; $totalGrossTbl = 0; $totalNetTbl = 0; $totalHppTbl = 0; $totalProfitTbl = 0; @endphp
                    @foreach($monthlyData as $m => $data)
                        @php
                            $totalOrdersTbl  += $data['orders_count'];
                            $totalGrossTbl   += $data['gross_revenue'];
                            $totalNetTbl     += $data['net_revenue'];
                            $totalHppTbl     += $data['total_hpp'];
                            $totalProfitTbl  += $data['net_profit'];
                        @endphp
                        <tr class="month-row {{ $data['orders_count'] == 0 ? 'text-muted' : '' }}">
                            <td class="ps-4 fw-semibold">
                                {{ $data['month_label'] }} {{ $selectedYear }}
                                @if(!$data['has_financial'] && $data['orders_count'] > 0)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;" title="Data dari order saja, belum ada data income Shopee">Est.</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($data['orders_count'] > 0)
                                    <span class="badge bg-primary-subtle text-primary">{{ number_format($data['orders_count']) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ $data['gross_revenue'] > 0 ? 'Rp '.number_format($data['gross_revenue'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end">
                                {{ $data['net_revenue'] > 0 ? 'Rp '.number_format($data['net_revenue'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end">
                                {{ $data['total_hpp'] > 0 ? 'Rp '.number_format($data['total_hpp'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end pe-4 fw-bold">
                                @if($data['orders_count'] > 0)
                                    <span class="{{ $data['net_profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                        {{ $data['net_profit'] >= 0 ? '' : '-' }}Rp {{ number_format(abs($data['net_profit']), 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td class="ps-4">TOTAL {{ $selectedYear }}</td>
                        <td class="text-center">{{ number_format($totalOrdersTbl) }}</td>
                        <td class="text-end">Rp {{ number_format($totalGrossTbl, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($totalNetTbl, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($totalHppTbl, 0, ',', '.') }}</td>
                        <td class="text-end pe-4 {{ $totalProfitTbl >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $totalProfitTbl >= 0 ? '' : '-' }}Rp {{ number_format(abs($totalProfitTbl), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Breakdown per Marketplace --}}
<div class="card section-card mb-4">
    <div class="card-header d-flex align-items-center gap-2 py-3">
        <i class="fas fa-store text-primary"></i>
        <span class="fw-bold">Performa Per Marketplace — {{ $selectedYear }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Marketplace / Toko</th>
                        <th class="text-center">Jml Order</th>
                        <th class="text-end">Pendapatan Bersih</th>
                        <th class="text-end">HPP / Modal</th>
                        <th class="text-end pe-4">Laba Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketplaces as $mp)
                        @if($mp->yearly_orders_count > 0)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $mp->name }}</div>
                                @if(!$mp->yearly_has_financial)
                                    <small class="text-warning"><i class="fas fa-exclamation-circle"></i> Estimasi (belum ada data income)</small>
                                @else
                                    <small class="text-success"><i class="fas fa-check-circle"></i> Data income tersedia</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary">{{ number_format($mp->yearly_orders_count) }}</span>
                            </td>
                            <td class="text-end">Rp {{ number_format($mp->yearly_net, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($mp->yearly_hpp, 0, ',', '.') }}</td>
                            <td class="text-end pe-4 fw-bold">
                                <span class="{{ $mp->yearly_profit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                    {{ $mp->yearly_profit >= 0 ? '' : '-' }}Rp {{ number_format(abs($mp->yearly_profit), 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada data marketplace untuk tahun {{ $selectedYear }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels  = @json(collect($monthlyData)->pluck('month_label')->values());
    const netRev  = @json(collect($monthlyData)->pluck('net_revenue')->values());
    const hpp     = @json(collect($monthlyData)->pluck('total_hpp')->values());
    const profit  = @json(collect($monthlyData)->pluck('net_profit')->values());

    const fmt = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));

    const ctx = document.getElementById('annualChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Pendapatan Bersih',
                    data: netRev,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderRadius: 6,
                    order: 1,
                },
                {
                    label: 'HPP / Modal',
                    data: hpp,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderRadius: 6,
                    order: 1,
                },
                {
                    label: 'Laba Bersih',
                    data: profit,
                    type: 'line',
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#198754',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4,
                    order: 0,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v/1000)) + 'K'
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
@endsection
