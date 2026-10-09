@extends('layouts.app')

@section('title', 'Data Transaksi')
@section('page_title', 'Data Transaksi')

@section('content')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h6 class="mb-0 text-uppercase fw-bold text-muted">Filter Data</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('order.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Toko / Akun</label>
                <select name="marketplace_account_id" class="form-select">
                    <option value="">Semua Toko</option>
                    @foreach($marketplaces as $mp)
                        <option value="{{ $mp->id }}" {{ request('marketplace_account_id') == $mp->id ? 'selected' : '' }}>
                            {{ $mp->account_name }} ({{ ucfirst($mp->marketplace->name ?? 'Unknown') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bulan Penjualan</label>
                <input type="month" name="month" class="form-control" value="{{ $currentMonth }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Order ID / Nama / No HP" value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('order.index') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Transaksi</h5>
        <div>
            <span class="badge bg-success rounded-pill me-2">Total Omzet: Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
            <span class="badge bg-info text-dark rounded-pill me-2">Total Income: Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
            <span class="badge bg-primary rounded-pill">{{ $orders->total() }} Transaksi</span>
        </div>
    </div>
    <div class="card-header bg-white border-0 pt-0 pb-3">
        <ul class="nav nav-pills">
            <li class="nav-item me-2">
                <a class="nav-link {{ $currentStatus === 'all' ? 'active' : 'bg-light text-dark' }}" href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => null]) }}">
                    Semua <span class="badge bg-secondary ms-1">{{ $countAll }}</span>
                </a>
            </li>
            <li class="nav-item me-2">
                <a class="nav-link {{ $currentStatus === 'success' ? 'active bg-success' : 'bg-light text-dark' }}" href="{{ request()->fullUrlWithQuery(['status' => 'success', 'page' => null]) }}">
                    Sukses <span class="badge {{ $currentStatus === 'success' ? 'bg-white text-success' : 'bg-success' }} ms-1">{{ $countSuccess }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'cancel' ? 'active bg-danger' : 'bg-light text-dark' }}" href="{{ request()->fullUrlWithQuery(['status' => 'cancel', 'page' => null]) }}">
                    Batal <span class="badge {{ $currentStatus === 'cancel' ? 'bg-white text-danger' : 'bg-danger' }} ms-1">{{ $countCancel }}</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="15%">Tanggal</th>
                        <th width="10%">Toko</th>
                        <th width="20%">Order ID</th>
                        <th width="20%">Pelanggan</th>
                        <th width="15%">Pengiriman</th>
                        <th width="10%">Status</th>
                        <th width="15%">Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge {{ strtolower($order->marketplaceAccount->marketplace->code ?? '') == 'shopee' ? 'bg-warning text-dark' : 'bg-dark' }}">
                                    {{ $order->marketplaceAccount->marketplace->name ?? 'Unknown' }} ({{ ucfirst($order->marketplaceAccount->marketplace->code ?? '') }})
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('order.show', $order->id) }}">{{ $order->external_order_id }}</a>
                            </td>
                            <td>
                                <strong>{{ $order->customer->customer_name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $order->customer->phone ?? '-' }}</small><br>
                                <small class="text-muted">{{ $order->customer->city ?? '-' }}</small>
                            </td>
                            <td>
                                <strong>{{ $order->shipping_provider ?: '-' }}</strong><br>
                                <small class="text-muted">{{ $order->tracking_number ?: '-' }}</small>
                            </td>
                            <td>
                                @php
                                    $statusClass = 'bg-secondary';
                                    switch($order->order_status) {
                                        case 'COMPLETED': $statusClass = 'bg-success'; break;
                                        case 'PENDING': $statusClass = 'bg-warning text-dark'; break;
                                        case 'CANCELLED': 
                                        case 'RETURNED': $statusClass = 'bg-danger'; break;
                                        case 'SHIPPED': $statusClass = 'bg-info text-dark'; break;
                                        case 'PAID': $statusClass = 'bg-primary'; break;
                                    }
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $order->order_status }}</span>
                                @if($order->order_substatus && $order->order_substatus != $order->order_status)
                                    <br><small class="text-muted" style="font-size:0.7em;">{{ $order->order_substatus }}</small>
                                @endif
                            </td>
                            <td>Rp {{ number_format($order->total_payment, 0, ',', '.') }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#orderDetailModal{{ $order->id }}">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data transaksi. Silakan lakukan import terlebih dahulu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0 d-flex justify-content-center">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Modals -->
@foreach($orders as $order)
    <div class="modal fade" id="orderDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Order: {{ $order->external_order_id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($order->items->whereNull('product_id')->count() > 0)
                        <div class="alert alert-warning py-2 mb-3" style="font-size: 0.9em;">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Perhatian:</strong> Terdapat produk yang belum dimapping. Stok tidak dapat dikurangi otomatis.
                        </div>
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Informasi Pelanggan</h6>
                            <p class="mb-1"><strong>Nama:</strong> {{ $order->customer->customer_name ?? '-' }}</p>
                            <p class="mb-1"><strong>No HP:</strong> {{ $order->customer->phone ?? '-' }}</p>
                            <p class="mb-1"><strong>Alamat:</strong> {{ $order->customer->address ?? '-' }}, {{ $order->customer->city ?? '-' }}, {{ $order->customer->province ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Pengiriman</h6>
                            <p class="mb-1"><strong>Kurir:</strong> {{ $order->shipping_provider ?? '-' }}</p>
                            <p class="mb-1"><strong>No Resi:</strong> {{ $order->tracking_number ?? '-' }}</p>
                            <p class="mb-1"><strong>Pembayaran:</strong> {{ $order->payment_method ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <h6>Daftar Produk</h6>
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Harga Awal</th>
                                <th>Harga Final</th>
                                <th>Diskon (Item)</th>
                                <th>HPP (Snapshot)</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Total HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if($item->product_id)
                                            {{ $item->product->sku ?? '-' }}
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Dimapping</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->product_name }}
                                        @if($item->product && $item->product->variation_name)
                                            <br><small class="text-muted">Var: {{ $item->product->variation_name }}</small>
                                        @endif
                                        @if(!$item->product_id)
                                            <br><small class="text-danger"><i class="bi bi-exclamation-circle"></i> Produk belum dimapping. Stok tidak dapat dikurangi otomatis.</small>
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($item->original_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">{{ $item->discount > 0 ? '-Rp ' . number_format($item->discount, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end text-primary">
                                        @if($item->hpp_snapshot === null)
                                            <span class="badge bg-secondary">Belum tersedia</span>
                                        @else
                                            Rp {{ number_format($item->hpp_snapshot, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-primary">
                                        @if($item->hpp_snapshot === null)
                                            -
                                        @else
                                            Rp {{ number_format($item->qty * $item->hpp_snapshot, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">
                                    {{ $order->order_status === 'COMPLETED' ? 'Realized HPP Order:' : 'Estimated HPP Order:' }}
                                </th>
                                <th class="text-end fw-bold text-primary fs-6">Rp {{ number_format($order->total_hpp, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Subtotal Produk</strong></td>
                                <td>Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Ongkos Kirim (Ditanggung Pembeli)</strong></td>
                                <td>Rp {{ number_format($order->financial->shipping_paid_by_buyer ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @if($order->financial && $order->financial->discount_seller > 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Diskon Penjual</strong></td>
                                <td class="text-danger">- Rp {{ number_format($order->financial->discount_seller, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && $order->financial->discount_marketplace > 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Diskon Marketplace</strong></td>
                                <td class="text-danger">- Rp {{ number_format($order->financial->discount_marketplace, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="table-light">
                                <th colspan="6" class="text-end">Total Pembayaran Pembeli</th>
                                <th>Rp {{ number_format($order->total_payment, 0, ',', '.') }}</th>
                            </tr>
                            
                            <!-- Biaya-biaya -->
                            @if($order->financial && $order->financial->admin_fee != 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Biaya Administrasi</strong></td>
                                <td class="text-danger">- Rp {{ number_format(abs($order->financial->admin_fee), 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && $order->financial->order_processing_fee != 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Biaya Proses Pesanan</strong></td>
                                <td class="text-danger">- Rp {{ number_format(abs($order->financial->order_processing_fee), 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && $order->financial->service_fee != 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Biaya Layanan / Promo XTRA</strong></td>
                                <td class="text-danger">- Rp {{ number_format(abs($order->financial->service_fee), 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && $order->financial->transaction_fee > 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Biaya Transaksi</strong></td>
                                <td class="text-danger">- Rp {{ number_format($order->financial->transaction_fee, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && ($order->financial->promotion_fee > 0 || $order->financial->affiliate_fee > 0))
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Biaya Promosi / Affiliate</strong></td>
                                <td class="text-danger">- Rp {{ number_format(($order->financial->promotion_fee ?? 0) + ($order->financial->affiliate_fee ?? 0), 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->financial && $order->financial->tax_amount > 0)
                            <tr>
                                <td colspan="6" class="text-end text-danger"><strong>Pajak (Tax)</strong></td>
                                <td class="text-danger">- Rp {{ number_format($order->financial->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            
                            <tr class="table-success">
                                <th colspan="6" class="text-end">Total Pendapatan Bersih (Net)</th>
                                <th>Rp {{ number_format($order->financial->total_income ?? 0, 0, ',', '.') }}
                                    @if(!$order->financial)
                                        <br><small class="text-muted fw-normal" style="font-size: 0.75em;">(Belum ada data laporan income)</small>
                                    @endif
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
