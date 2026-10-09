@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi HPP')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Laporan Rekapitulasi HPP</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan HPP</li>
            </ol>
        </div>
    </div>
@stop

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
</style>

<div class="container-fluid">
    <!-- Filter Section -->
    <div class="card card-primary card-outline mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filter Laporan</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('report.hpp') }}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label for="start_date">Mulai Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="quick_month" class="text-primary fw-bold"><i class="far fa-calendar-alt"></i> Pilih Bulan</label>
                        <input type="month" id="quick_month" class="form-control border-primary" onchange="setMonth(this.value, 'start_date', 'end_date')" title="Pilih bulan untuk otomatis mengisi tanggal mulai dan sampai">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="status">Status Order</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <option value="COMPLETED" {{ $status == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                            <option value="CANCELLED" {{ $status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                            <option value="UNPAID" {{ $status == 'UNPAID' ? 'selected' : '' }}>UNPAID</option>
                            <option value="READY_TO_SHIP" {{ $status == 'READY_TO_SHIP' ? 'selected' : '' }}>READY_TO_SHIP</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="search">Pencarian (SKU/Produk)</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Cari..." value="{{ $search }}">
                    </div>
                    <div class="col-md-1 mb-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Section -->
    <div class="row mb-4 g-4">
        <!-- Total Qty Terjual -->
        <div class="col-md-4">
            <div class="card bg-gradient-info text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #17a2b8, #1fc8e3);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Total Qty Terjual</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-boxes text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">{{ number_format($totalQty, 0, ',', '.') }}</h3>
                </div>
                <i class="fas fa-boxes summary-icon"></i>
            </div>
        </div>
        
        <!-- Total HPP -->
        <div class="col-md-4">
            <div class="card bg-gradient-success text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, #28a745, #34ce57);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Total HPP</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-money-bill-wave text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">Rp {{ number_format($totalHpp, 2, ',', '.') }}</h3>
                </div>
                <i class="fas fa-money-bill-wave summary-icon"></i>
            </div>
        </div>
        
        <!-- Item Tanpa HPP -->
        <div class="col-md-4">
            <div class="card text-white h-100 border-0 shadow-sm summary-card position-relative" style="background: linear-gradient(45deg, {{ $itemsWithoutHpp > 0 ? '#dc3545, #f55b69' : '#28a745, #34ce57' }});">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title text-white-50 text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Item Tanpa HPP</h6>
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">{{ number_format($itemsWithoutHpp, 0, ',', '.') }} Item</h3>
                </div>
                <i class="fas fa-exclamation-triangle summary-icon"></i>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Rincian Item Terjual</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">HPP/Unit</th>
                        <th class="text-right">Total HPP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->sku ?? '-' }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-center">{{ number_format($item->total_qty, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if($item->hpp_snapshot !== null)
                                    Rp {{ number_format($item->hpp_snapshot, 2, ',', '.') }}
                                @else
                                    <span class="badge badge-danger">KOSONG</span>
                                @endif
                            </td>
                            <td class="text-right text-bold">
                                Rp {{ number_format($item->subtotal_hpp, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Data tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $items->links('pagination::bootstrap-4') }}
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
@stop
