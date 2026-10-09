@extends('layouts.app')

@section('title', 'Rekonsiliasi Shopee - Kulashop')
@section('page_title', 'Rekonsiliasi Shopee')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <form method="GET" action="{{ route('reconciliation.shopee.index') }}" class="card shadow-sm border-0">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center">
                    <span class="text-muted me-2 fw-bold small text-uppercase">Toko:</span>
                    <select name="toko" class="form-select form-select-sm" style="width: 200px;">
                        <option value="">Semua Toko Shopee</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('toko') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="d-flex align-items-center border-start ps-3">
                    <span class="text-muted me-2 fw-bold small text-uppercase">Tipe Tanggal:</span>
                    <select name="date_type" class="form-select form-select-sm" style="width: 220px;">
                        <option value="order_date" {{ request('date_type') == 'order_date' ? 'selected' : '' }}>Berdasarkan Tanggal Order</option>
                        <option value="released_at" {{ request('date_type') == 'released_at' ? 'selected' : '' }}>Berdasarkan Tgl Dana Dilepaskan</option>
                    </select>
                </div>

                <div class="d-flex align-items-center border-start ps-3">
                    <span class="text-muted me-2 fw-bold small text-uppercase">Periode:</span>
                    <input type="date" name="start_date" id="recon_start_date" class="form-control form-control-sm" value="{{ request('start_date') }}" max="{{ date('Y-m-d') }}">
                    <span class="mx-2 text-muted">s/d</span>
                    <input type="date" name="end_date" id="recon_end_date" class="form-control form-control-sm" value="{{ request('end_date') }}" max="{{ date('Y-m-d') }}">
                </div>
                
                <div class="d-flex align-items-center border-start ps-3">
                    <span class="text-primary me-2 fw-bold small text-uppercase"><i class="far fa-calendar-alt"></i> Set Bulan:</span>
                    <input type="month" class="form-control form-control-sm border-primary" onchange="setMonth(this.value, 'recon_start_date', 'recon_end_date')" title="Pilih Bulan">
                </div>
                
                <div class="ms-auto">
                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fas fa-filter me-1"></i> Terapkan</button>
                    @if(request('toko') || request('start_date'))
                        <a href="{{ route('reconciliation.shopee.index', ['tab' => $tab]) }}" class="btn btn-sm btn-light border ms-1">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@php
    $selectedAccountName = 'Semua Toko Shopee';
    if(request('toko')) {
        $acc = $accounts->firstWhere('id', request('toko'));
        if($acc) {
            $selectedAccountName = 'Shopee - ' . $acc->name;
        }
    } else {
        $selectedAccountName = 'Shopee - Keseluruhan';
    }
@endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white border-0 py-3">
        <h6 class="mb-0 fw-bold text-uppercase"><i class="fas fa-chart-pie me-2"></i> RECONCILIATION SUMMARY: {{ $selectedAccountName }}</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 border-end">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted w-50">Total Order</td>
                        <td class="fw-bold">: {{ number_format($totalOrders) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Matched</td>
                        <td class="fw-bold text-success">: {{ number_format($totalMatched) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Waiting Income</td>
                        <td class="fw-bold text-primary">: {{ number_format($totalWaitingIncome) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Needs Review</td>
                        <td class="fw-bold text-warning">: {{ number_format($totalSelisih) }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6 d-flex flex-column justify-content-center">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted w-50">Total Order Payment</td>
                        <td class="fw-bold fs-5">: Rp {{ number_format($totalOrderPayment, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Income</td>
                        <td class="fw-bold fs-5 text-success">: Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 {{ $tab == 'matched' ? 'bg-success text-white' : '' }}">
            <h6 class="text-uppercase fw-bold mb-1 {{ $tab == 'matched' ? 'text-white' : 'text-success' }}">Lengkap</h6>
            <h3 class="mb-0 fw-bold">{{ number_format($totalMatched) }}</h3>
            <small class="{{ $tab == 'matched' ? 'text-white-50' : 'text-muted' }}">Status: matched</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 {{ $tab == 'waiting_income' ? 'bg-primary text-white' : '' }}">
            <h6 class="text-uppercase fw-bold mb-1 {{ $tab == 'waiting_income' ? 'text-white' : 'text-primary' }}">Belum Cair</h6>
            <h3 class="mb-0 fw-bold">{{ number_format($totalWaitingIncome) }}</h3>
            <small class="{{ $tab == 'waiting_income' ? 'text-white-50' : 'text-muted' }}">Status: waiting_income</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 {{ $tab == 'needs_review' ? 'bg-warning text-dark' : '' }}">
            <h6 class="text-uppercase fw-bold mb-1 {{ $tab == 'needs_review' ? 'text-dark' : 'text-warning' }}">Needs Review</h6>
            <h3 class="mb-0 fw-bold">{{ number_format($totalSelisih) }}</h3>
            <small class="{{ $tab == 'needs_review' ? 'text-dark-50' : 'text-muted' }}">Status: needs_review</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 {{ $tab == 'waiting_order' ? 'bg-danger text-white' : '' }}">
            <h6 class="text-uppercase fw-bold mb-1 {{ $tab == 'waiting_order' ? 'text-white' : 'text-danger' }}">Waiting Order</h6>
            <h3 class="mb-0 fw-bold">{{ number_format($totalBermasalah) }}</h3>
            <small class="{{ $tab == 'waiting_order' ? 'text-white-50' : 'text-muted' }}">Status: waiting_order</small>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <ul class="nav nav-tabs border-bottom-0">
            <li class="nav-item">
                <a class="nav-link fw-bold {{ $tab == 'matched' ? 'active text-success' : 'text-muted' }}" 
                   href="{{ route('reconciliation.shopee.index', ['tab' => 'matched', 'toko' => request('toko'), 'date_type' => request('date_type'), 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">
                   <i class="fas fa-check-circle me-1"></i> Order Lengkap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold {{ $tab == 'waiting_income' ? 'active text-primary' : 'text-muted' }}" 
                   href="{{ route('reconciliation.shopee.index', ['tab' => 'waiting_income', 'toko' => request('toko'), 'date_type' => request('date_type'), 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">
                   <i class="fas fa-clock me-1"></i> Belum Cair
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold {{ $tab == 'needs_review' ? 'active text-warning text-dark' : 'text-muted' }}" 
                   href="{{ route('reconciliation.shopee.index', ['tab' => 'needs_review', 'toko' => request('toko'), 'date_type' => request('date_type'), 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">
                   <i class="fas fa-exclamation-triangle me-1"></i> Needs Review
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold {{ $tab == 'waiting_order' ? 'active text-danger' : 'text-muted' }}" 
                   href="{{ route('reconciliation.shopee.index', ['tab' => 'waiting_order', 'toko' => request('toko'), 'date_type' => request('date_type'), 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">
                   <i class="fas fa-times-circle me-1"></i> Waiting Order
                </a>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-0">
        @if($tab == 'waiting_income')
        <div class="alert alert-info m-3 mb-0 border-0 shadow-sm">
            <i class="fas fa-info-circle me-2"></i> <strong>Order Menunggu Income:</strong> Income belum ditemukan. Import file Shopee Income terlebih dahulu jika dana/record sudah tersedia.
        </div>
        @endif
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>No Pesanan</th>
                        <th>Tanggal Order</th>
                        
                        @if($tab == 'matched' || $tab == 'needs_review')
                        <th>Customer</th>
                        <th class="text-end" title="Total Payment dari Data Order ERP">Order Payment (ERP)</th>
                        <th class="text-end" title="Total Payment dari Data Income Shopee">Payment (Shopee)</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-end" title="Total Income Bersih dari Shopee">Income Bersih</th>
                        @elseif($tab == 'waiting_income')
                        <th class="text-end">Total Payment</th>
                        @elseif($tab == 'waiting_order')
                        <th class="text-end">Payment (Shopee)</th>
                        <th class="text-end">Income Bersih</th>
                        @endif
                        
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        @if($tab == 'matched' || $tab == 'needs_review')
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'order']) }}" class="text-decoration-none">
                                    {{ $row->external_order_id }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $row->order_date ? $row->order_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $row->customer->name ?? '-' }}</td>
                            
                            <td class="text-end">Rp {{ number_format($row->total_payment, 0, ',', '.') }}</td>
                            
                            @php
                                $shopeePayment = $tab == 'matched' ? $row->financial->total_payment : $row->financial_payment;
                                $selisih = $row->total_payment - $shopeePayment;
                                $hasSelisih = abs($selisih) > 0.01;
                            @endphp
                            <td class="text-end">Rp {{ number_format($shopeePayment, 0, ',', '.') }}</td>
                            
                            <td class="text-end {{ $hasSelisih ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $hasSelisih ? 'Rp ' . number_format(abs($selisih), 0, ',', '.') : '-' }}
                            </td>
                            
                            <td class="text-end text-success fw-bold">Rp {{ number_format($tab == 'matched' ? $row->financial->total_income : $row->financial_income, 0, ',', '.') }}</td>
                            
                            <td class="text-center">
                                @if($tab == 'matched')
                                    <span class="badge bg-success">MATCHED</span>
                                @elseif($tab == 'needs_review')
                                    <span class="badge bg-warning text-dark">NEEDS REVIEW</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'order']) }}" class="btn btn-sm btn-light border"><i class="fas fa-search"></i></a>
                            </td>
                        </tr>
                        @elseif($tab == 'waiting_income')
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'order']) }}" class="text-decoration-none">
                                    {{ $row->external_order_id }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $row->order_date ? $row->order_date->format('d-m-Y') : '-' }}</td>
                            <td class="text-end">Rp {{ number_format($row->total_payment, 0, ',', '.') }}</td>
                            <td class="text-center"><span class="badge bg-primary">WAITING INCOME</span></td>
                            <td class="text-end">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'order']) }}" class="btn btn-sm btn-light border"><i class="fas fa-search"></i></a>
                            </td>
                        </tr>
                        @elseif($tab == 'waiting_order')
                        @php 
                            $rawIncome = $row->raw_data['total_penghasilan'] ?? $row->raw_data['penghasilan'] ?? 0; 
                            $parsedIncome = is_numeric($rawIncome) ? $rawIncome : preg_replace('/[^0-9.-]/', '', $rawIncome);
                            $rawPayment = $row->raw_data['jumlah_dibayar_pembeli'] ?? 0;
                            $parsedPayment = is_numeric($rawPayment) ? $rawPayment : preg_replace('/[^0-9.-]/', '', $rawPayment);
                        @endphp
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'error']) }}" class="text-decoration-none text-danger">
                                    {{ $row->external_order_id ?? '-' }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $row->created_at->format('d-m-Y') }}</td>
                            <td class="text-muted fst-italic">Unknown</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end">Rp {{ number_format((float)$parsedPayment, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end">Rp {{ number_format((float)$parsedIncome, 0, ',', '.') }}</td>
                            <td class="text-center"><span class="badge bg-danger">WAITING ORDER</span></td>
                            <td class="text-end">
                                <a href="{{ route('reconciliation.shopee.show', ['id' => $row->id, 'tab' => $tab, 'type' => 'error']) }}" class="btn btn-sm btn-light border"><i class="fas fa-search"></i></a>
                                <a href="{{ route('import.index') }}" class="btn btn-sm btn-outline-primary" style="padding: 0.1rem 0.4rem; font-size: 0.75rem;">Import Order</a>
                            </td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fs-3 text-success mb-3 d-block"></i>
                            Tidak ada data untuk kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($data->hasPages())
        <div class="p-3 border-top bg-light">
            {{ $data->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('.table').DataTable({
            "paging": false,
            "info": false,
            "searching": true,
            "ordering": true,
            "language": {
                "search": "Cari No Pesanan:",
                "zeroRecords": "Tidak ada data yang cocok dengan pencarian"
            }
        });
    });
</script>
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
