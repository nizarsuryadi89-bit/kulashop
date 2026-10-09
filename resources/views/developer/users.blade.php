@extends('layouts.app')
@section('title', 'Developer - User Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Developer - User Management <i class="fas fa-users-cog text-primary ms-2"></i></h4>
        <a href="{{ route('developer.index') }}" class="btn btn-light btn-sm fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Developer Panel
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- List Users -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-users text-info me-2"></i> Daftar Pengguna Sistem</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="fw-bold text-uppercase ps-4" style="font-size: 0.8rem;">Nama</th>
                                    <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Email</th>
                                    <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Role</th>
                                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size: 0.8rem;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $user->name }}</td>
                                    <td class="text-muted">{{ $user->email }}</td>
                                    <td>
                                        @if($user->role === 'developer')
                                            <span class="badge bg-dark">Developer</span>
                                        @elseif($user->role === 'owner')
                                            <span class="badge bg-danger">Owner</span>
                                        @else
                                            <span class="badge bg-info text-dark">Admin</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Btn Reset Password -->
                                        <button type="button" class="btn btn-sm btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#resetModal{{ $user->id }}">
                                            <i class="fas fa-key"></i> Reset
                                        </button>
                                        
                                        <!-- Btn Delete -->
                                        @if(auth()->id() !== $user->id)
                                        <form action="{{ route('developer.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Reset Password Modal -->
                                <div class="modal fade" id="resetModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                                      <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Reset Password: {{ $user->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <form action="{{ route('developer.users.reset-password', $user->id) }}" method="POST">
                                          @csrf
                                          <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="new_password" required minlength="6">
                                                <div class="form-text">Minimal 6 karakter.</div>
                                            </div>
                                          </div>
                                          <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Password</button>
                                          </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-user-plus text-success me-2"></i> Tambah User Baru</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="{{ route('developer.users.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Nama</label>
                            <input type="text" class="form-control" name="name" required value="{{ old('name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" required value="{{ old('email') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Password</label>
                            <input type="text" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="owner" {{ old('role') == 'owner' ? 'selected' : '' }}>Owner</option>
                                <option value="developer" {{ old('role') == 'developer' ? 'selected' : '' }}>Developer</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-save me-1"></i> Buat User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
