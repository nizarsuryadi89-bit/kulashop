@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page_title', 'Profil Saya')

@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Ganti Password</h6>
            </div>
            <div class="card-body p-4">
                @if(session('status') === 'password-updated')
                    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success" style="border-radius: 12px;">
                        <i class="fas fa-check-circle me-2"></i> Password berhasil diperbarui.
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger" style="border-radius: 12px;">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-bold">Password Saat Ini</label>
                        <input type="password" class="form-control bg-light border-0" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password Baru</label>
                        <input type="password" class="form-control bg-light border-0" id="password" name="password" required autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-bold">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control bg-light border-0" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold">
                            <i class="fas fa-save me-2"></i> Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-4" style="border-radius: 16px;">
            <div class="card-body p-4 text-center">
                 <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px; height:60px;">
                    <i class="fas fa-user fs-3"></i>
                </div>
                <h5 class="fw-bold">{{ $user->name }}</h5>
                <p class="text-muted mb-1">{{ $user->email }}</p>
                <span class="badge bg-secondary rounded-pill px-3 py-1">{{ ucfirst($user->role) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
