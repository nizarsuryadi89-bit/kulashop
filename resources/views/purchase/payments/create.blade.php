@extends('layouts.app')

@section('title', 'Input Pembayaran')
@section('page_title', 'Input Pembayaran PO')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Pilih Purchase Order Belum Lunas</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('purchase.payments.create') }}" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <select name="po_id" class="form-select select2" required>
                            <option value="">Pilih PO...</option>
                            @foreach($orders as $o)
                                <option value="{{ $o->id }}" {{ (isset($order) && $order->id == $o->id) ? 'selected' : '' }}>
                                    {{ $o->po_number }} - {{ $o->supplier->name ?? '' }} (Outstanding: Rp {{ number_format($o->outstanding_amount, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(isset($order))
    <div class="col-md-12">
        <form action="{{ route('purchase.payments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                    <h5 class="mb-0 fw-bold">Informasi Pembayaran</h5>
                    <span class="badge bg-info">PO: {{ $order->po_number }}</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Total Tagihan PO:</strong> Rp {{ number_format($order->grand_total, 0, ',', '.') }}<br>
                            <strong>Telah Dibayar:</strong> Rp {{ number_format($order->paid_amount, 0, ',', '.') }}
                        </div>
                        <div class="text-end">
                            <strong class="text-danger fs-5">Sisa Tagihan (Outstanding)</strong><br>
                            <span class="text-danger fw-bold fs-4">Rp {{ number_format($order->outstanding_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" 
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control fw-bold fs-5 text-primary @error('amount') is-invalid @enderror" 
                                   value="{{ old('amount', $order->outstanding_amount) }}" min="1" max="{{ $order->outstanding_amount }}" required>
                            <small class="text-muted">Maksimal: Rp {{ number_format($order->outstanding_amount, 0, ',', '.') }}</small>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="transfer_bank" {{ old('payment_method') == 'transfer_bank' ? 'selected' : '' }}>Transfer Bank</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash / Tunai</option>
                                <option value="ewallet" {{ old('payment_method') == 'ewallet' ? 'selected' : '' }}>E-Wallet / Qris</option>
                                <option value="lainnya" {{ old('payment_method') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Referensi (M-Banking/Bukti)</label>
                            <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" 
                                   value="{{ old('reference_number') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end py-3">
                    <a href="{{ route('purchase.payments.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan pembayaran ini?')">
                        <i class="fas fa-save me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
