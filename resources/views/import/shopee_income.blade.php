@extends('layouts.app')

@section('title', 'Import Shopee Income')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Import Laporan Penghasilan Shopee</h1>
                <a href="{{ route('import.history') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-history me-1"></i> Riwayat Import
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upload File Penghasilan / Income</h6>
                </div>
                <div class="card-body">
                    <form id="previewForm" action="{{ route('import.shopee_income.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Pilih Akun Toko Shopee</label>
                                <select name="marketplace_account_id" class="form-select @error('marketplace_account_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Toko --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('marketplace_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_name }} ({{ $account->marketplace->name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('marketplace_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Pastikan Anda memilih akun toko yang benar sesuai dengan file laporan.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">File Excel Laporan Penghasilan</label>
                                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Upload file laporan penghasilan yang di-download langsung dari Seller Centre.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-info text-white" id="btnPreview">
                                <i class="fas fa-search me-1"></i> Preview Income
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Result Area (Hidden by default) -->
            <div id="previewArea" class="card shadow-sm border-0 mb-4 d-none">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Hasil Analisis Preview</h6>
                    <span class="badge bg-warning text-dark">Preview belum mengubah database transaksi</span>
                </div>
                <div class="card-body">
                    
                    <div id="previewSummary">
                        <!-- Summary metrics will be injected here via AJAX -->
                    </div>

                    <div class="alert alert-info mt-4 mb-0">
                        <i class="fas fa-info-circle me-2"></i> 
                        Pastikan Anda telah mengimpor Laporan <strong>Pesanan (Order)</strong> terlebih dahulu sebelum mengimpor Laporan Penghasilan. Order yang tidak ditemukan tidak akan mendapatkan data finansial.
                    </div>

                    <form id="processForm" action="{{ route('import.shopee_income.process') }}" method="POST" class="mt-4 border-top pt-3">
                        @csrf
                        <input type="hidden" name="marketplace_account_id" id="process_account_id">
                        <input type="hidden" name="temp_file_path" id="process_temp_path">
                        <input type="hidden" name="original_file_name" id="process_file_name">
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmData" required>
                                <label class="form-check-label fw-bold" for="confirmData">
                                    Saya yakin data preview di atas sudah benar dan siap diproses
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success" id="btnProcess" disabled>
                                <i class="fas fa-save me-1"></i> Proses Import Income
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const previewForm = document.getElementById('previewForm');
        const btnPreview = document.getElementById('btnPreview');
        const previewArea = document.getElementById('previewArea');
        const previewSummary = document.getElementById('previewSummary');
        
        const processForm = document.getElementById('processForm');
        const btnProcess = document.getElementById('btnProcess');
        const confirmCheck = document.getElementById('confirmData');
        
        // Handle confirm checkbox
        confirmCheck.addEventListener('change', function() {
            btnProcess.disabled = !this.checked;
        });

        // Handle Preview Submit
        previewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const accountId = formData.get('marketplace_account_id');
            const originalFile = formData.get('file').name;
            
            // Loading state
            btnPreview.disabled = true;
            btnPreview.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menganalisis...';
            previewArea.classList.add('d-none');
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': formData.get('_token')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Show success UI
                previewArea.classList.remove('d-none');
                previewSummary.innerHTML = data.html;
                
                // Setup process form
                document.getElementById('process_account_id').value = accountId;
                document.getElementById('process_temp_path').value = data.temp_path || ''; // We need to make sure the controller returns this
                document.getElementById('process_file_name').value = originalFile;
                
                // Scroll to preview
                previewArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(error => {
                alert('Gagal melakukan preview: ' + error.message);
            })
            .finally(() => {
                // Reset button
                btnPreview.disabled = false;
                btnPreview.innerHTML = '<i class="fas fa-search me-1"></i> Preview Income';
                
                // Reset checkbox
                confirmCheck.checked = false;
                btnProcess.disabled = true;
            });
        });

        // Handle Process Submit
        processForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirmCheck.checked) return;
            
            const formData = new FormData(this);
            
            // Loading state
            btnProcess.disabled = true;
            btnProcess.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': formData.get('_token')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                if (data.batch_id) {
                    btnProcess.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan ke Database...';
                    checkStatus(data.batch_id);
                }
            })
            .catch(error => {
                alert('Gagal memproses import: ' + error.message);
                btnProcess.disabled = false;
                btnProcess.innerHTML = '<i class="fas fa-save me-1"></i> Proses Import Income';
            });
        });

        function checkStatus(batchId) {
            const checkInterval = setInterval(() => {
                fetch(`/import/${batchId}/status`)
                    .then(res => res.json())
                    .then(batch => {
                        if (batch.status === 'completed' || batch.status === 'completed_with_errors' || batch.status === 'failed') {
                            clearInterval(checkInterval);
                            
                            let numFmt = new Intl.NumberFormat('id-ID');
                            let swalIcon = batch.status === 'completed' ? 'success' : (batch.status === 'failed' ? 'error' : 'warning');
                            let swalTitle = 'Import selesai.';
                            
                            if (batch.status === 'failed') {
                                swalTitle = 'Import Gagal (Fatal)';
                            } else if (batch.error_rows > 0) {
                                swalTitle = `Import selesai dengan ${numFmt.format(batch.error_rows)} error`;
                            }
                            
                            let htmlContent = `
<div class="text-start ms-4" style="font-family: monospace; font-size: 14px;">
Total Row &nbsp;: ${numFmt.format(batch.total_rows)}<br>
Inserted &nbsp;&nbsp;: ${numFmt.format(batch.success_rows)}<br>
Updated &nbsp;&nbsp;&nbsp;: ${numFmt.format(batch.updated_rows)}<br>
Duplicate &nbsp;: ${numFmt.format(batch.duplicate_rows)}<br>
Error &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="${batch.error_rows > 0 ? 'text-danger fw-bold' : ''}">${numFmt.format(batch.error_rows)}</span>
</div>
`;
                            if (batch.error_rows > 0 && batch.status !== 'failed') {
                                htmlContent += `<hr><span class="text-danger">Lihat detail import untuk memeriksa data bermasalah.</span>`;
                            } else if (batch.status === 'failed') {
                                htmlContent = 'Terjadi kesalahan sistem (Global Error) saat memproses file.<br><br>Silakan buka halaman Detail untuk melihat pesan error log.';
                            }

                            Swal.fire({
                                icon: swalIcon,
                                title: swalTitle,
                                html: htmlContent,
                                showCancelButton: true,
                                confirmButtonText: '<i class="fas fa-eye me-1"></i> Lihat Detail Import',
                                cancelButtonText: 'Tutup & Lihat Riwayat',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = `/import/${batch.id}`;
                                } else {
                                    window.location.href = "{{ route('import.history') }}";
                                }
                            });
                        }
                    });
            }, 2000);
        }
    });
</script>
@endpush
