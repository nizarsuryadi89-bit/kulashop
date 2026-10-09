@extends('layouts.app')

@section('title', 'Import Shopee Order - Kulashop')
@section('page_title', 'Import Shopee Order')

@push('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .preview-table-container {
            display: none;
            margin-top: 20px;
        }
        #previewTable {
            font-size: 0.85rem;
        }
        #previewTable th {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
<div class="row">
    <!-- Form Import -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Upload Shopee Order</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="marketplace_account_id" class="form-label">Toko / Akun Shopee</label>
                        <select name="marketplace_account_id" id="marketplace_account_id" class="form-select select2" required>
                            <option value="">Pilih Toko Shopee...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->account_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">File Excel (.xlsx, .xls)</label>
                        <input type="file" name="file" id="file" class="form-control" required accept=".xlsx,.xls">
                    </div>
                    
                    <button type="button" id="btnPreview" class="btn btn-secondary w-100 mb-2">
                        <i class="fas fa-search me-2"></i> Preview Import
                    </button>
                </form>

                <!-- Hidden form for actual process -->
                <form id="processForm" action="{{ route('import.shopee.process') }}" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="marketplace_account_id" id="process_account_id">
                    <input type="hidden" name="temp_file_path" id="process_temp_file">
                    <input type="hidden" name="original_file_name" id="process_original_name">
                    <button type="submit" id="btnProcess" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-2"></i> Proses Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Table -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Preview Data (Max 10 Baris Pertama)</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted" id="emptyPreviewMsg">
                    <div class="my-5">
                        <i class="fas fa-file-excel fs-1 text-light mb-3"></i>
                        <p>Silakan upload dan klik <strong>Preview Import</strong> untuk melihat ringkasan *dry-run* sebelum diproses.</p>
                    </div>
                </div>

                <div class="preview-table-container" style="display: none;">
                    
                    <!-- Summary Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Informasi Import</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" width="100">Toko</td>
                                        <td class="fw-bold" id="summ_toko">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">File</td>
                                        <td class="fw-bold text-truncate" style="max-width: 200px;" id="summ_file">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light h-100">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Ringkasan Kalkulasi (Dry-Run)</h6>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div class="fs-4 fw-bold" id="summ_total_row">0</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Total Baris</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fs-4 fw-bold text-primary" id="summ_unik_order">0</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Order Unik</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fs-4 fw-bold text-success" id="summ_new_order">0</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Order Baru</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fs-4 fw-bold text-info" id="summ_update_order">0</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Order Diupdate</small>
                                    </div>
                                    <div class="col-12 mt-3" id="unmapped_sku_container" style="display: none;">
                                        <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem;">
                                            <i class="fas fa-exclamation-triangle me-1"></i> 
                                            <span id="summ_new_sku" class="fw-bold">0</span> item belum memiliki mapping produk ERP.
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2" id="duplicate_order_container" style="display: none;">
                                        <div class="alert alert-info py-2 mb-0" style="font-size: 0.85rem;">
                                            <i class="fas fa-info-circle me-1"></i> 
                                            Data order sudah ada. Sistem melakukan update terhadap data yang ditemukan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Sample Data (Mapped)</h6>
                    <div class="table-responsive">
                        <table id="previewTable" class="table table-striped table-bordered w-100">
                            <thead>
                                <tr id="previewHeaders"></tr>
                            </thead>
                            <tbody id="previewBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Init Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            let dataTable = null;

            $('#btnPreview').on('click', function() {
                let formData = new FormData($('#uploadForm')[0]);
                let accountId = $('#marketplace_account_id').val();
                let fileInput = $('#file').val();

                if(!accountId) {
                    Swal.fire('Error', 'Silakan pilih Toko / Akun Shopee terlebih dahulu.', 'error');
                    return;
                }

                if(!fileInput) {
                    Swal.fire('Error', 'Silakan pilih File Excel yang akan diimport.', 'error');
                    return;
                }

                // Show loading
                let btn = $(this);
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Membaca File...');
                btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route("import.shopee.preview") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        btn.html(originalText);
                        btn.prop('disabled', false);

                        if(response.success) {
                            // Populate Summary
                            let tokoText = $('#marketplace_account_id option:selected').text();
                            $('#summ_toko').text(tokoText);
                            $('#summ_file').text(response.file_name);
                            $('#summ_total_row').text(response.total_rows);
                            $('#summ_unik_order').text(response.total_unique_orders);
                            $('#summ_new_order').text(response.new_orders);
                            $('#summ_update_order').text(response.updated_orders); // fixed from update_orders to updated_orders
                            
                            $('#summ_new_sku').text(response.unmapped_skus);
                            if (response.unmapped_skus > 0) {
                                $('#unmapped_sku_container').show();
                            } else {
                                $('#unmapped_sku_container').hide();
                            }

                            if (response.new_orders == 0 && response.updated_orders > 0) {
                                $('#duplicate_order_container').show();
                            } else {
                                $('#duplicate_order_container').hide();
                            }

                            // Render Preview Table
                            renderPreview(response.headers, response.preview_rows);
                            
                            // Prepare Hidden Process Form
                            $('#process_account_id').val(accountId);
                            $('#process_temp_file').val(response.file_path);
                            $('#process_original_name').val(response.file_name);
                            
                            // Show Process Button
                            $('#processForm').show();

                            // Bind submit event for processForm if not bound yet
                            $('#processForm').off('submit').on('submit', function(e) {
                                e.preventDefault();
                                let pBtn = $('#btnProcess');
                                let pText = pBtn.html();
                                pBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Mengimport...');
                                pBtn.prop('disabled', true);
                                
                                $.ajax({
                                    url: '{{ route("import.shopee.process") }}',
                                    type: 'POST',
                                    data: $(this).serialize(),
                                    success: function(res) {
                                        pBtn.html(pText);
                                        pBtn.prop('disabled', false);

                                        if (res.success) {
                                            let numFmt = new Intl.NumberFormat('id-ID');
                                            let title = res.summary.error_rows > 0 ? `Import selesai dengan ${numFmt.format(res.summary.error_rows)} error` : 'Import selesai.';
                                            
                                            let htmlContent = `
<div class="text-start ms-4" style="font-family: monospace; font-size: 14px;">
Total Row &nbsp;: ${numFmt.format(res.summary.total_rows)}<br>
Inserted &nbsp;&nbsp;: ${numFmt.format(res.summary.success_rows)}<br>
Updated &nbsp;&nbsp;&nbsp;: ${numFmt.format(res.summary.updated_rows)}<br>
Duplicate &nbsp;: ${numFmt.format(res.summary.duplicate_rows)}<br>
Error &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="${res.summary.error_rows > 0 ? 'text-danger fw-bold' : ''}">${numFmt.format(res.summary.error_rows)}</span>
</div>
`;

                                            if (res.summary.error_rows > 0) {
                                                htmlContent += `<hr><span class="text-danger">Lihat detail import untuk memeriksa data bermasalah.</span>`;
                                            }

                                            Swal.fire({
                                                title: title,
                                                html: htmlContent,
                                                icon: res.summary.error_rows > 0 ? 'warning' : 'success',
                                                showCancelButton: true,
                                                confirmButtonText: '<i class="fas fa-eye me-1"></i> Lihat Detail Import',
                                                cancelButtonText: 'Tutup & Lihat Riwayat',
                                                allowOutsideClick: false
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    window.location.href = `/import/${res.summary.batch_id}`;
                                                } else {
                                                    window.location.href = '{{ route("import.history") }}';
                                                }
                                            });
                                        }
                                    },
                                    error: function(xhr) {
                                        pBtn.html(pText);
                                        pBtn.prop('disabled', false);
                                        
                                        let m = 'Terjadi kesalahan saat memproses import.';
                                        if(xhr.responseJSON && xhr.responseJSON.error) {
                                            m = xhr.responseJSON.error;
                                        } else if(xhr.responseJSON && xhr.responseJSON.message) {
                                            m = xhr.responseJSON.message;
                                        }
                                        Swal.fire('Error', m, 'error');
                                    }
                                });
                            });

                            Swal.fire({
                                icon: 'success',
                                title: 'Preview Siap',
                                text: `Berhasil memproses dry-run dari ${response.total_rows} baris.`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.html(originalText);
                        btn.prop('disabled', false);
                        
                        let msg = 'Terjadi kesalahan saat memproses file.';
                        if(xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        } else if(xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            function renderPreview(headers, rows) {
                // Destroy existing table
                if (dataTable !== null) {
                    dataTable.destroy();
                    $('#previewHeaders').empty();
                    $('#previewBody').empty();
                }

                $('#emptyPreviewMsg').hide();
                $('.preview-table-container').show();

                // Append headers
                let thHtml = '';
                headers.forEach(function(header) {
                    thHtml += `<th>${header || '-'}</th>`;
                });
                $('#previewHeaders').html(thHtml);

                // Append body
                let tbHtml = '';
                rows.forEach(function(row) {
                    tbHtml += '<tr>';
                    row.forEach(function(cell) {
                        tbHtml += `<td>${cell || ''}</td>`;
                    });
                    tbHtml += '</tr>';
                });
                $('#previewBody').html(tbHtml);

                // Init DataTable
                dataTable = $('#previewTable').DataTable({
                    scrollX: true,
                    pageLength: 5,
                    lengthMenu: [5, 10],
                    ordering: false, // Keep original excel order
                    language: {
                        search: "Cari data:",
                        lengthMenu: "Tampilkan _MENU_ baris",
                        info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ baris (Preview)"
                    }
                });
            }

            $('#processForm').on('submit', function() {
                let btn = $('#btnProcess');
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Menjadwalkan Import...');
                btn.prop('disabled', true);
            });
        });
    </script>
@endpush
