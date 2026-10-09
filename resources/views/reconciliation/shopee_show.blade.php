@extends('layouts.app')

@section('title', 'Detail Rekonsiliasi - Kulashop')
@section('page_title', 'Detail Rekonsiliasi: ' . $order->external_order_id)

@section('content')
<div class="mb-3">
    <a href="{{ route('reconciliation.shopee.index', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Data
    </a>
</div>

<div class="row">
    <!-- Kolom Order -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i> DATA ORDER (SISTEM ERP)</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tbody>
                        <tr>
                            <td class="text-muted w-50">No Pesanan</td>
                            <td class="fw-bold">{{ $order->external_order_id }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td><span class="badge bg-secondary">{{ $order->order_status }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal</td>
                            <td>{{ $order->order_date ? $order->order_date->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Customer</td>
                            <td>{{ $order->customer->name ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="fw-bold mt-4 mb-2 text-muted border-bottom pb-2">Produk Dibeli</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <table class="table table-sm table-borderless mt-3">
                    <tbody>
                        <tr>
                            <td class="text-muted text-end w-75">Subtotal Produk:</td>
                            <td class="text-end fw-bold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted text-end">Discount:</td>
                            <td class="text-end text-danger">-Rp {{ number_format($order->discount_total, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted text-end">Shipping:</td>
                            <td class="text-end">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-end fw-bold fs-6">Total Payment:</td>
                            <td class="text-end fw-bold fs-6 text-primary">Rp {{ number_format($order->total_payment, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Income -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2"></i> DATA INCOME (SHOPEE)</h6>
            </div>
            <div class="card-body">
                @if($order->financial)
                
                @php
                    $f = $order->financial;
                    $calculatedIncome = 
                        $f->product_amount + 
                        $f->shipping_paid_by_buyer + 
                        $f->discount_marketplace - 
                        $f->discount_seller - 
                        $f->admin_fee - 
                        $f->order_processing_fee - 
                        $f->transaction_fee - 
                        $f->service_fee - 
                        $f->promotion_fee - 
                        $f->affiliate_fee - 
                        $f->other_fee - 
                        $f->refund_amount - 
                        $f->tax_amount;
                @endphp

                <table class="table table-sm table-borderless">
                    <tbody>
                        <tr>
                            <td class="w-50">Harga Produk</td>
                            <td class="text-end fw-bold text-success">(+) Rp {{ number_format($f->product_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Ongkir (Dibayar Pembeli)</td>
                            <td class="text-end text-success">(+) Rp {{ number_format($f->shipping_paid_by_buyer, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Diskon / Voucher Shopee</td>
                            <td class="text-end text-success">(+) Rp {{ number_format($f->discount_marketplace, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Diskon dari Penjual</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->discount_seller, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Administrasi</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->admin_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Proses Pesanan</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->order_processing_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Transaksi</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->transaction_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Layanan</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->service_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Promosi / Kampanye</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->promotion_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Komisi Affiliate</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->affiliate_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Lainnya</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->other_fee, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Refund ke Pembeli</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->refund_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td>Pajak</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($f->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-light">
                            <td class="fw-bold">Total Kalkulasi (Estimasi)</td>
                            <td class="text-end fw-bold">Rp {{ number_format($calculatedIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-success border-top border-2 border-success">
                            <td class="fw-bold fs-6">TOTAL INCOME SHOPEE <small class="fw-normal text-muted d-block">(Authoritative / Aktual)</small></td>
                            <td class="text-end fw-bold fs-6 text-success align-middle">Rp {{ number_format($f->total_income, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted pt-3 border-0">Tanggal Pencairan</td>
                            <td class="text-end pt-3 border-0">{{ $f->released_at ? $f->released_at->format('d M Y') : 'Belum Cair' }}</td>
                        </tr>
                    </tbody>
                </table>


                @else
                <div class="text-center py-5 text-muted px-4">
                    <i class="fas fa-file-excel fs-1 text-primary mb-3 d-block"></i>
                    <h5 class="fw-bold">Order Menunggu Income</h5>
                    <p class="mb-4">Income belum ditemukan. Import file Shopee Income terlebih dahulu jika dana/record sudah tersedia.</p>
                    <a href="{{ route('import.index') }}" class="btn btn-outline-primary"><i class="fas fa-upload me-2"></i> Import Income Shopee</a>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-5 {{ $order->financial ? (abs($order->total_payment - $order->financial->total_payment) <= 0.01 ? 'border-success' : 'border-danger') : 'border-secondary' }}">
                <div class="card-body py-4">
                    @php
                        $erpPayment = $order->total_payment;
                        $hasFinancial = $order->financial !== null;
                        
                        if (!$hasFinancial) {
                            $flag = 'NOT AVAILABLE';
                            $flagClass = 'bg-secondary';
                            $shopeePaymentText = '-';
                        } else {
                            $shopeePayment = $order->financial->total_payment;
                            $shopeePaymentText = 'Rp ' . number_format($shopeePayment, 0, ',', '.');
                            
                            if (abs($erpPayment - $shopeePayment) <= 0.01) {
                                $flag = 'MATCH';
                                $flagClass = 'bg-success';
                            } else {
                                $flag = 'DIFFERENT';
                                $flagClass = 'bg-danger';
                            }
                        }
                    @endphp
                    
                    <h6 class="text-uppercase fw-bold text-muted mb-4"><i class="fas fa-flag-checkered me-2"></i> Financial Reconciliation Flag</h6>
                    
                    <div class="row text-center align-items-center">
                        <div class="col-md-4 border-end">
                            <div class="text-muted mb-2">Order Payment</div>
                            <div class="fs-4 fw-bold">Rp {{ number_format($erpPayment, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4 border-end">
                            <div class="text-muted mb-2">Income Payment</div>
                            <div class="fs-4 fw-bold {{ $hasFinancial ? '' : 'text-muted' }}">{{ $shopeePaymentText }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted mb-2">Payment Check</div>
                            <div class="fs-3"><span class="badge {{ $flagClass }} rounded-pill px-4 py-2 shadow-sm">{{ $flag }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
