@extends('layouts.app')

@section('title', 'Purchase Payments')
@section('page_title', 'Pembayaran Pembelian (Purchase Payment)')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Pembayaran</h5>
        <a href="{{ route('purchase.payments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Input Pembayaran
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('purchase.payments.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari nomor Payment, PO, atau Referensi..." value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Bayar</th>
                        <th>No. Pembayaran</th>
                        <th>Referensi PO</th>
                        <th>Supplier</th>
                        <th>Metode Bayar</th>
                        <th>No. Referensi (Bank/dll)</th>
                        <th>Jumlah Bayar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pay)
                    <tr>
                        <td>{{ $pay->payment_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-success">{{ $pay->payment_number }}</span></td>
                        <td>
                            <a href="{{ route('purchase.orders.show', $pay->purchase_order_id) }}">
                                {{ $pay->purchaseOrder->po_number }}
                            </a>
                        </td>
                        <td class="fw-semibold">{{ $pay->purchaseOrder->supplier->name }}</td>
                        <td><span class="text-uppercase">{{ $pay->payment_method }}</span></td>
                        <td>{{ $pay->reference_number ?? '-' }}</td>
                        <td class="fw-bold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('purchase.payments.show', $pay->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data pembayaran pembelian.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
