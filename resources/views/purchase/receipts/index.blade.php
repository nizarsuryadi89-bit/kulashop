@extends('layouts.app')

@section('title', 'Goods Receipts')
@section('page_title', 'Penerimaan Barang (Goods Receipt)')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Goods Receipt</h5>
        <a href="{{ route('purchase.receipts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Input Penerimaan
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('purchase.receipts.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari nomor GR, PO, atau Surat Jalan..." value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Terima</th>
                        <th>No. Receipt</th>
                        <th>No. Surat Jalan</th>
                        <th>Referensi PO</th>
                        <th>Supplier</th>
                        <th>Gudang</th>
                        <th>Diterima Oleh</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $gr)
                    <tr>
                        <td>{{ $gr->receipt_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-success">{{ $gr->receipt_number }}</span></td>
                        <td>{{ $gr->delivery_order_number ?? '-' }}</td>
                        <td>
                            <a href="{{ route('purchase.orders.show', $gr->purchase_order_id) }}">
                                {{ $gr->purchaseOrder->po_number }}
                            </a>
                        </td>
                        <td class="fw-semibold">{{ $gr->supplier->name }}</td>
                        <td>{{ $gr->warehouse->name }}</td>
                        <td>{{ $gr->receiver->name ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('purchase.receipts.show', $gr->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data penerimaan barang.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $receipts->links() }}
        </div>
    </div>
</div>
@endsection
