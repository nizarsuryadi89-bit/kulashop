@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier')
@section('page_title', 'Master Supplier')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-bold">{{ $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier' }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ $supplier->exists ? route('purchase.suppliers.update', $supplier->id) : route('purchase.suppliers.store') }}" method="POST">
            @csrf
            @if($supplier->exists)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kode Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="supplier_code" class="form-control @error('supplier_code') is-invalid @enderror" 
                           value="{{ old('supplier_code', $supplier->supplier_code) }}" required>
                    @error('supplier_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $supplier->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" 
                           value="{{ old('contact_person', $supplier->contact_person) }}">
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                           value="{{ old('phone', $supplier->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email', $supplier->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', $supplier->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $supplier->status) == 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" 
                           value="{{ old('city', $supplier->city) }}">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Provinsi</label>
                    <input type="text" name="province" class="form-control @error('province') is-invalid @enderror" 
                           value="{{ old('province', $supplier->province) }}">
                    @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $supplier->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan Supplier
                </button>
                <a href="{{ route('purchase.suppliers.index') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
