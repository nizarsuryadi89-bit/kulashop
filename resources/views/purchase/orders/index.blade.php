@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page_title', 'Purchase Orders')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Purchase Order</h5>
        <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Buat PO
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('purchase.orders.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Cari nomor PO atau supplier..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status PO</option>
                    <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ordered" {{ $status == 'ordered' ? 'selected' : '' }}>Ordered</option>
                    <option value="partial_received" {{ $status == 'partial_received' ? 'selected' : '' }}>Partial Received</option>
                    <option value="received" {{ $status == 'received' ? 'selected' : '' }}>Received</option>
                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="payment_status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status Pembayaran</option>
                    <option value="unpaid" {{ $payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="partial" {{ $payment_status == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ $payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Nomor PO</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $po)
                    <tr>
                        <td>{{ $po->order_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-secondary">{{ $po->po_number }}</span></td>
                        <td class="fw-semibold">{{ $po->supplier->name }}</td>
                        <td>{{ $po->warehouse->name }}</td>
                        <td class="fw-bold">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                        <td>
                            @if($po->status == 'draft') <span class="badge bg-secondary">Draft</span>
                            @elseif($po->status == 'ordered') <span class="badge bg-primary">Ordered</span>
                            @elseif($po->status == 'partial_received') <span class="badge bg-warning text-dark">Partial Received</span>
                            @elseif($po->status == 'received') <span class="badge bg-success">Received</span>
                            @else <span class="badge bg-danger">Cancelled</span> @endif
                        </td>
                        <td>
                            @if($po->payment_status == 'unpaid') <span class="badge bg-danger">Unpaid</span>
                            @elseif($po->payment_status == 'partial') <span class="badge bg-warning text-dark">Partial</span>
                            @else <span class="badge bg-success">Paid</span> @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('purchase.orders.show', $po->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data Purchase Order.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
