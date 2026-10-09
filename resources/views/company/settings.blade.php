@extends('layouts.app')

@section('title', 'Pengaturan Perusahaan')
@section('page_title', 'Profil Perusahaan')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Informasi Perusahaan</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4 text-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            @if(isset($company) && $company->logo)
                                <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo Perusahaan" class="img-thumbnail mb-2" style="max-height: 150px; max-width: 150px; object-fit: contain;">
                            @else
                                <div class="bg-light d-inline-flex align-items-center justify-content-center border rounded mb-2" style="height: 150px; width: 150px;">
                                    <span class="text-muted"><i class="fas fa-building fa-3x"></i></span>
                                </div>
                            @endif
                            <div class="mt-2">
                                <label for="logo" class="form-label d-block fw-medium">Logo Perusahaan</label>
                                <input type="file" name="logo" id="logo" class="form-control form-control-sm mx-auto" style="max-width: 250px;" accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Maks 2MB. JPG, PNG, SVG</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            @if(isset($company) && $company->login_background)
                                <img src="{{ asset('storage/' . $company->login_background) }}" alt="Login Background" class="img-thumbnail mb-2" style="max-height: 150px; max-width: 150px; object-fit: cover;">
                            @else
                                <div class="bg-light d-inline-flex align-items-center justify-content-center border rounded mb-2" style="height: 150px; width: 150px;">
                                    <span class="text-muted"><i class="fas fa-image fa-3x"></i></span>
                                </div>
                            @endif
                            <div class="mt-2">
                                <label for="login_background" class="form-label d-block fw-medium">Gambar Background Login</label>
                                <input type="file" name="login_background" id="login_background" class="form-control form-control-sm mx-auto" style="max-width: 250px;" accept="image/png, image/jpeg, image/jpg">
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Maks 2MB. JPG, PNG</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $company->name ?? '') }}" required placeholder="Contoh: PT. Maju Jaya">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Perusahaan</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $company->email ?? '') }}" placeholder="email@perusahaan.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $company->phone ?? '') }}" placeholder="0812xxxx">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="Alamat lengkap perusahaan...">{{ old('address', $company->address ?? '') }}</textarea>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Tampilan & Tema</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="font_family" class="form-label">Jenis Huruf (Font)</label>
                            <select name="font_family" id="font_family" class="form-select">
                                <option value="" {{ (old('font_family', $company->font_family ?? '') == '') ? 'selected' : '' }}>Sistem Default (Inter)</option>
                                <option value="'Roboto', sans-serif" {{ (old('font_family', $company->font_family ?? '') == "'Roboto', sans-serif") ? 'selected' : '' }}>Roboto</option>
                                <option value="'Poppins', sans-serif" {{ (old('font_family', $company->font_family ?? '') == "'Poppins', sans-serif") ? 'selected' : '' }}>Poppins</option>
                                <option value="'Open Sans', sans-serif" {{ (old('font_family', $company->font_family ?? '') == "'Open Sans', sans-serif") ? 'selected' : '' }}>Open Sans</option>
                                <option value="'Montserrat', sans-serif" {{ (old('font_family', $company->font_family ?? '') == "'Montserrat', sans-serif") ? 'selected' : '' }}>Montserrat</option>
                                <option value="'Lora', serif" {{ (old('font_family', $company->font_family ?? '') == "'Lora', serif") ? 'selected' : '' }}>Lora (Serif)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sidebar_theme" class="form-label">Warna Latar Sidebar</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="sidebar_theme" id="sidebar_theme" class="form-control form-control-color" value="{{ old('sidebar_theme', $company->sidebar_theme ?? '#0f172a') }}" title="Pilih warna latar">
                                <span class="text-muted small">Default: Gelap (#0f172a)</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sidebar_text_theme" class="form-label">Warna Teks Sidebar</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="sidebar_text_theme" id="sidebar_text_theme" class="form-control form-control-color" value="{{ old('sidebar_text_theme', $company->sidebar_text_theme ?? '#ffffff') }}" title="Pilih warna teks">
                                <span class="text-muted small">Default: Putih (#ffffff)</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
