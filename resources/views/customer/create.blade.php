@extends('layouts.app')

@section('title', 'Tambah Pelanggan Baru')
@section('page_title', 'Tambah Pelanggan Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="mb-0 fw-bold">Form Tambah Pelanggan</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger rounded-3 border-0 shadow-sm">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control bg-light" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">No. Handphone</label>
                            <input type="text" name="phone" class="form-control bg-light" value="{{ old('phone') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Lengkap</label>
                        <textarea name="address" class="form-control bg-light" rows="3">{{ old('address') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Desa / Kelurahan</label>
                            <input type="text" name="village" class="form-control bg-light" value="{{ old('village') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kecamatan</label>
                            <input type="text" name="district" class="form-control bg-light" value="{{ old('district') }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kota / Kabupaten</label>
                            <input type="text" name="city" class="form-control bg-light" value="{{ old('city') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Provinsi</label>
                            <input type="text" name="province" class="form-control bg-light" value="{{ old('province') }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('customers.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
