@extends('layouts.app')

@section('title', 'Detail Pembayaran: ' . $payment->payment_number)
@section('page_title', 'Detail Pembayaran PO')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3 text-center border-bottom mb-3">
                <h4 class="mb-0 fw-bold">Bukti Pembayaran</h4>
                <div class="text-muted small">No. {{ $payment->payment_number }}</div>
            </div>
            <div class="card-body px-5 pb-5">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted mb-1">Dibayarkan Kepada:</div>
                        <div class="fw-bold fs-5">{{ $payment->purchaseOrder->supplier->name }}</div>
                        <div class="text-muted small">{{ $payment->purchaseOrder->supplier->address ?? '' }}</div>
                        <div class="text-muted small">{{ $payment->purchaseOrder->supplier->phone ?? '' }}</div>
                    </div>
                    <div class="col-sm-6 text-end">
                        <div class="text-muted mb-1">Referensi Tagihan:</div>
                        <div class="fw-bold fs-5">
                            <a href="{{ route('purchase.orders.show', $payment->purchase_order_id) }}" class="text-decoration-none">
                                {{ $payment->purchaseOrder->po_number }}
                            </a>
                        </div>
                        <div class="text-muted small">Tgl Order: {{ $payment->purchaseOrder->order_date->format('d/m/Y') }}</div>
                    </div>
                </div>

                <table class="table table-bordered mb-4">
                    <thead class="table-light">
                        <tr>
                            <th colspan="2" class="text-center">Informasi Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="30%" class="fw-semibold">Tanggal Pembayaran</td>
                            <td>{{ $payment->payment_date->format('l, d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Metode Pembayaran</td>
                            <td class="text-uppercase">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Nomor Referensi</td>
                            <td>{{ $payment->reference_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Catatan</td>
                            <td>{{ $payment->notes ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="bg-light p-4 rounded text-center border">
                    <div class="text-muted mb-2">Total Telah Dibayar (Pada transaksi ini)</div>
                    <h2 class="text-success fw-bold mb-0">Rp {{ number_format($payment->amount, 0, ',', '.') }}</h2>
                </div>

                <div class="row mt-4 text-center text-muted small">
                    <div class="col-4 border-end">
                        Total Tagihan PO:<br>
                        Rp {{ number_format($payment->purchaseOrder->grand_total, 0, ',', '.') }}
                    </div>
                    <div class="col-4 border-end">
                        Total Terbayar PO:<br>
                        Rp {{ number_format($payment->purchaseOrder->paid_amount, 0, ',', '.') }}
                    </div>
                    <div class="col-4">
                        Sisa Tagihan PO:<br>
                        Rp {{ number_format($payment->purchaseOrder->outstanding_amount, 0, ',', '.') }}
                    </div>
                </div>

                <div class="mt-5 text-muted small">
                    Dicetak oleh: {{ $payment->creator->name ?? 'System' }} pada {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
                </div>
            </div>
            <div class="card-footer bg-white text-center py-3">
                <a href="{{ route('purchase.payments.index') }}" class="btn btn-outline-secondary">Kembali ke Daftar</a>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Cetak Bukti</button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
    }
    .card-footer, .btn, .navbar, .sidebar {
        display: none !important;
    }
}
</style>
@endsection
