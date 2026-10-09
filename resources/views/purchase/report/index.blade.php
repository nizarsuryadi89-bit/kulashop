@extends('layouts.app')

@section('title', 'Laporan Pembelian Tahunan')
@section('page_title', 'Laporan Pembelian Tahunan')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('purchase.report') }}" method="GET" class="d-flex align-items-center">
                    <label for="year" class="form-label me-3 mb-0 fw-bold">Pilih Tahun Laporan:</label>
                    <select name="year" id="year" class="form-select w-auto me-3" onchange="this.form.submit()">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase mb-2">Total Transaksi</h6>
                <h3 class="fw-bold mb-0">{{ number_format($summary['total_orders'], 0, ',', '.') }} PO</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase mb-2">Total Pembelian</h6>
                <h3 class="fw-bold mb-0">Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase mb-2">Total Terbayar</h6>
                <h3 class="fw-bold mb-0">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase mb-2">Total Hutang (Outstanding)</h6>
                <h3 class="fw-bold mb-0">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Detail Laporan Bulanan - Tahun {{ $year }}</h5>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i> Cetak</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Bulan</th>
                                <th class="text-center">Jml Transaksi</th>
                                <th class="text-end">Total Pembelian</th>
                                <th class="text-end">Total Terbayar</th>
                                <th class="text-end">Total Hutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $data)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $data['month'] }}</td>
                                <td class="text-center">{{ number_format($data['total_orders'], 0, ',', '.') }}</td>
                                <td class="text-end text-primary fw-bold">Rp {{ number_format($data['total_amount'], 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($data['total_paid'], 0, ',', '.') }}</td>
                                <td class="text-end text-danger">Rp {{ number_format($data['total_outstanding'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">TOTAL KESELURUHAN</td>
                                <td class="text-center">{{ number_format($summary['total_orders'], 0, ',', '.') }}</td>
                                <td class="text-end text-primary">Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</td>
                                <td class="text-end text-danger">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .card, .card * { visibility: visible; }
    .card { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
    .btn, .sidebar, .navbar { display: none !important; }
}
</style>
@endsection
