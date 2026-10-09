@extends('layouts.app')

@section('title', 'Edit Pengeluaran')
@section('page_title', 'Edit Pengeluaran')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="expense_date" class="form-label fw-medium">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date) }}" required>
                            @error('expense_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <label for="expense_category_id" class="form-label fw-medium">Kategori <span class="text-danger">*</span></label>
                            <select name="expense_category_id" id="expense_category_id" class="form-select" required>
                                <option value="" disabled>Pilih kategori...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('expense_category_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-medium">Deskripsi <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $expense->description) }}" required placeholder="Contoh: Pembayaran Gaji Karyawan Bulan Agustus">
                        @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label fw-medium">Nominal (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" name="amount" id="amount" class="form-control border-start-0 ps-0" value="{{ old('amount', $expense->amount) }}" required min="0.01" step="0.01">
                        </div>
                        @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label fw-medium">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="" disabled>Pilih metode...</option>
                                <option value="cash" {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                                <option value="bank_transfer" {{ old('payment_method', $expense->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                <option value="e_wallet" {{ old('payment_method', $expense->payment_method) == 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                                <option value="other" {{ old('payment_method', $expense->payment_method) == 'other' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                            @error('payment_method') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <label for="reference_number" class="form-label fw-medium">Nomor Referensi</label>
                            <input type="text" name="reference_number" id="reference_number" class="form-control" value="{{ old('reference_number', $expense->reference_number) }}" placeholder="Contoh: INV-202308-001">
                            @error('reference_number') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-medium">Status Pengeluaran <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="draft" {{ old('status', $expense->status) == 'draft' ? 'selected' : '' }}>Draft (Belum dihitung ke Laba Bersih)</option>
                            <option value="paid" {{ old('status', $expense->status) == 'paid' ? 'selected' : '' }}>Lunas / Paid (Masuk Laba Bersih)</option>
                            <option value="cancelled" {{ old('status', $expense->status) == 'cancelled' ? 'selected' : '' }}>Dibatalkan (Cancelled)</option>
                        </select>
                        <small class="text-muted d-block mt-1">Hanya status <strong>Lunas</strong> yang akan memotong Laba Bersih di laporan keuangan.</small>
                        @error('status') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-medium">Keterangan / Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $expense->notes) }}</textarea>
                        @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="receipt_file" class="form-label fw-medium">Bukti Pembayaran / Struk <span class="text-muted fw-normal">(Opsional)</span></label>
                        @if($expense->receipt_file)
                            <div class="mb-2">
                                <a href="{{ Storage::url($expense->receipt_file) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-external-link-alt me-1"></i> Lihat Bukti Saat Ini
                                </a>
                            </div>
                        @endif
                        <input type="file" name="receipt_file" id="receipt_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted d-block mt-1">Biarkan kosong jika tidak ingin mengubah bukti. Format didukung: JPG, PNG, PDF. Maks: 2MB.</small>
                        @error('receipt_file') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <hr class="mb-4 text-muted">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('expenses.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
