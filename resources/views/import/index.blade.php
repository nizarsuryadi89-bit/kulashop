@extends('layouts.app')

@section('title', 'Import Excel - Kulashop')
@section('page_title', 'Import Data Toko')

@section('content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Upload File Excel</h6>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#sopImportModal">
                    <i class="fas fa-info-circle me-1"></i> SOP Import
                </button>
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
                <form id="uploadForm" action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="marketplace_id" class="form-label">Marketplace</label>
                        <select id="marketplace_id" class="form-select" required>
                            <option value="">Pilih Marketplace...</option>
                            @foreach($marketplaces as $mp)
                                <option value="{{ $mp->id }}">{{ $mp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="marketplace_account_id" class="form-label">Toko / Akun</label>
                        <select name="marketplace_account_id" id="marketplace_account_id" class="form-select" required disabled>
                            <option value="">-- Pilih Marketplace Terlebih Dahulu --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="import_type" class="form-label">Jenis File</label>
                        <select name="import_type" id="import_type" class="form-select" required>
                            <option value="">Pilih Jenis File...</option>
                            <option value="shopee_order">Shopee - Laporan Pesanan (Order)</option>
                            <option value="shopee_income">Shopee - Laporan Penghasilan (Income)</option>
                            <option value="tiktok_order">TikTok - Laporan Pesanan (Order)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">File Excel (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" id="file" class="form-control" required accept=".xlsx,.xls,.csv">
                    </div>
                    <button id="uploadBtn" type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Upload & Proses</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        @if($batches->count() > 0)
        @php $latest = $batches->first(); @endphp
        <div class="card shadow-sm border-0 mb-4 {{ $latest->status == 'completed' ? 'border-success border-top border-3' : ($latest->status == 'processing' ? 'border-warning border-top border-3' : '') }}">
            <div class="card-header bg-white border-0 pt-4 pb-2">
                <h6 class="mb-0 text-uppercase fw-bold {{ $latest->status == 'completed' ? 'text-success' : ($latest->status == 'processing' ? 'text-warning' : 'text-muted') }}">
                    @if($latest->status == 'processing' || $latest->status == 'pending')
                        <i class="fas fa-spinner fa-spin me-2"></i> MEMPROSES FILE...
                    @elseif($latest->status == 'completed')
                        <i class="fas fa-check-circle me-2"></i> IMPORT SELESAI
                    @elseif($latest->status == 'completed_with_errors')
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i> SELESAI DENGAN ERROR
                    @else
                        <i class="fas fa-times-circle me-2 text-danger"></i> IMPORT GAGAL
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Marketplace & Toko</span>
                        <span class="fw-bold">{{ $latest->marketplaceAccount->marketplace->name ?? 'Unknown' }} - {{ $latest->marketplaceAccount->account_name ?? 'Unknown' }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Jenis</span>
                        <span class="fw-bold">
                            @if(str_contains($latest->import_type, 'income'))
                                Income (Penghasilan)
                            @else
                                Order (Pesanan)
                            @endif
                        </span>
                    </div>
                    <div class="col-12 mt-2">
                        <span class="text-muted small d-block">File Excel</span>
                        <span class="fw-bold text-truncate d-block">{{ $latest->file_name ?? 'Unknown' }}</span>
                    </div>
                </div>
                
                @php
                    $summary = is_string($latest->summary) ? json_decode($latest->summary, true) : ($latest->summary ?? []);
                    $totalRows = $summary['total_rows'] ?? $latest->total_rows;
                    $uniqueOrders = $summary['unique_orders'] ?? '-';
                    $newOrders = $summary['new_orders'] ?? '-';
                    $updatedOrders = $summary['updated_orders'] ?? $latest->updated_rows;
                    $itemSuccess = $latest->success_rows;
                    $unmappedSkus = $summary['unmapped_skus'] ?? '-';
                    $errorRows = $latest->error_rows;
                @endphp
                <div class="bg-light p-3 rounded mb-3 border">
                    <div class="row text-center g-3">
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold">{{ $totalRows }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Total Row</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-primary">{{ $uniqueOrders }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Order Unik</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-success">{{ $newOrders }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Order Baru</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-info">{{ $updatedOrders }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Order Update</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-success">{{ $itemSuccess }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Item Berhasil</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-warning">{{ $unmappedSkus }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">SKU Unmapped</div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="h5 mb-0 fw-bold text-danger">{{ $errorRows }}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Error</div>
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('import.show', $latest->id) }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-search me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        @endif

        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Riwayat Import Terbaru</h6>

            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Toko / Akun</th>
                            <th>Status</th>
                            <th>Hasil Import</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $batch->marketplaceAccount->marketplace->name ?? '-' }} <br><small class="text-muted">{{ $batch->marketplaceAccount->account_name ?? '-' }}</small></td>
                            <td>
                                @if($batch->status == 'completed')
                                    <span class="badge bg-success">COMPLETED</span>
                                @elseif($batch->status == 'completed_with_errors')
                                    <span class="badge bg-warning text-dark">WITH ERRORS</span>
                                @elseif($batch->status == 'failed')
                                    <span class="badge bg-danger">FAILED</span>
                                @elseif($batch->status == 'processing')
                                    <span class="badge bg-warning text-dark status-processing"><i class="fas fa-circle-notch fa-spin me-1"></i>PROCESSING</span>
                                @else
                                    <span class="badge bg-secondary status-pending"><i class="fas fa-spinner fa-spin me-1"></i>PENDING</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    Total: {{ $batch->total_rows }} | Baru: {{ $batch->success_rows }} | Update: {{ $batch->updated_rows }} | Duplikat: {{ $batch->duplicate_rows }} | Error: {{ $batch->error_rows }}
                                </div>
                            </td>
                            <td>
                                @if($batch->error_rows > 0)
                                    <a href="{{ route('import.show', $batch->id) }}" class="btn btn-sm btn-outline-danger">Detail Error</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Belum ada riwayat import.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal SOP Import -->
<div class="modal fade" id="sopImportModal" tabindex="-1" aria-labelledby="sopImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title fw-bold" id="sopImportModalLabel">SOP Import Data Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-dark">
        <h6 class="fw-bold text-danger mb-3">
            <i class="fas fa-exclamation-triangle me-1"></i> PENTING: Urutan Import Tidak Boleh Terbalik!
        </h6>
        
        <p>Sistem ERP KulaShop didesain dengan struktur <strong>Order-First</strong>. Artinya, data Pesanan (Order) adalah fondasi utama, dan data Penghasilan (Income) adalah pelengkap data finansial.</p>

        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border-start border-4 border-info">
            <div class="fs-1 text-info me-3">1</div>
            <div>
                <h6 class="fw-bold mb-1">PERTAMA: Import Laporan Pesanan (Order)</h6>
                <small class="text-muted">Unduh dan import Laporan Pesanan untuk periode yang bersangkutan. Ini berfungsi untuk mencatatkan pesanan fisik beserta barang yang keluar.</small>
            </div>
        </div>

        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border-start border-4 border-success">
            <div class="fs-1 text-success me-3">2</div>
            <div>
                <h6 class="fw-bold mb-1">KEDUA: Import Laporan Penghasilan (Income)</h6>
                <small class="text-muted">Setelah file order berhasil masuk 100%, barulah import Laporan Penghasilan. Sistem otomatis akan menempelkan data uang masuk ke setiap pesanan.</small>
            </div>
        </div>

        <div class="alert alert-warning mb-0" style="font-size: 0.9rem;">
            <strong>Apa yang terjadi jika urutannya terbalik?</strong><br>
            Sistem akan gagal membaca Income dan menampilkan error <code>order_not_found</code> karena induk pesanannya belum diimpor ke dalam database. Jika terlanjur terjadi, cukup import file Order-nya terlebih dahulu, lalu klik tombol <b>Retry</b> pada detail error.
        </div>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Saya Mengerti</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const btn = document.getElementById('uploadBtn');
    
    if (form) {
        form.addEventListener('submit', function() {
            // Change button to loading state
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sedang Memproses...';
            btn.classList.add('disabled');
            setTimeout(() => { btn.disabled = true; }, 0);
        });
    }

    const marketplaceSelect = document.getElementById('marketplace_id');
    const accountSelect = document.getElementById('marketplace_account_id');

    if (marketplaceSelect) {
        marketplaceSelect.addEventListener('change', function() {
            const marketplaceId = this.value;
            
            // Clear current options
            accountSelect.innerHTML = '<option value="">Memuat data toko...</option>';
            accountSelect.disabled = true;
            
            if (marketplaceId) {
                fetch(`/marketplaces/${marketplaceId}/accounts`)
                    .then(response => response.json())
                    .then(data => {
                        accountSelect.innerHTML = '<option value="">Pilih Toko...</option>';
                        if (data.length > 0) {
                            data.forEach(account => {
                                const option = document.createElement('option');
                                option.value = account.id;
                                option.textContent = `${account.account_name} (${account.account_code})`;
                                accountSelect.appendChild(option);
                            });
                            accountSelect.disabled = false;
                        } else {
                            accountSelect.innerHTML = '<option value="">Belum ada toko aktif</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching accounts:', error);
                        accountSelect.innerHTML = '<option value="">Gagal mengambil data toko</option>';
                    });
            } else {
                accountSelect.innerHTML = '<option value="">-- Pilih Marketplace Terlebih Dahulu --</option>';
            }
        });
    }

    function pollStatus() {
        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update table body silently
            const newTbody = doc.querySelector('.table tbody').innerHTML;
            document.querySelector('.table tbody').innerHTML = newTbody;
            
            // Check if there are still processing jobs
            if (doc.querySelector('.status-pending, .status-processing')) {
                setTimeout(pollStatus, 3000);
            }
        });
    }

    // Check if there are any pending or processing jobs on the page initially
    const hasActiveProcess = document.querySelector('.status-pending, .status-processing');
    if (hasActiveProcess) {
        setTimeout(pollStatus, 3000);
    }
});
</script>
@endpush
