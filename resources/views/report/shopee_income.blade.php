@extends('layouts.app')

@section('title', 'Rincian Laporan Penghasilan Shopee')
@section('page_title', 'Rincian Laporan Penghasilan Shopee')

@section('content')
<style>
    .shopee-table th, .shopee-table td {
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #edf2f9;
        border-right: none;
        border-left: none;
    }
    .shopee-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .shopee-header td {
        background-color: #ff5722 !important; /* Shopee Orange */
        color: white !important;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.85rem;
        border-bottom: none !important;
    }
    .shopee-sub-header td {
        background-color: #f1f4f8 !important;
        font-weight: 700;
        color: #2c3e50;
        font-size: 0.95rem;
    }
    .shopee-sub-section {
        padding-left: 30px !important;
        font-weight: 600;
        color: #34495e;
    }
    .shopee-sub-sub-section {
        padding-left: 50px !important;
        color: #7f8c8d;
    }
    .shopee-total td {
        background-color: #198754 !important;
        color: white !important;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .text-value {
        font-weight: 600;
        color: #2c3e50;
    }
    .text-negative {
        color: #dc3545;
    }
</style>

<div class="mb-3">
    <a href="{{ route('report.financial', ['month' => $month]) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Laporan Keuangan
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table shopee-table mb-0" style="width: 100%; min-width: 800px; font-size: 0.9em; font-family: Arial, sans-serif;">
                <thead>
                    <tr>
                        <th colspan="2" style="background-color: white; border:none;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fe/Shopee.svg/1200px-Shopee.svg.png" height="40" alt="Shopee">
                        </th>
                        <th class="text-end fs-4" style="background-color: white; border:none; vertical-align: bottom;">
                            Laporan Penghasilan
                        </th>
                    </tr>
                    <tr class="shopee-header">
                        <td colspan="3">Rincian Laporan</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 30%">Username (Penjual)</td>
                        <td colspan="2">{{ $marketplace->name }}</td>
                    </tr>
                    <tr>
                        <td>Periode</td>
                        <td colspan="2">{{ \Carbon\Carbon::parse($month)->format('F Y') }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="border:none; height: 10px;"></td>
                    </tr>

                    <!-- RINGKASAN PENGHASILAN -->
                    <tr class="shopee-header">
                        <td colspan="2">Ringkasan Penghasilan</td>
                        <td class="text-end">Rp</td>
                    </tr>
                    
                    <!-- 1. Total Pendapatan -->
                    <tr class="shopee-sub-header">
                        <td colspan="2">1. Total Pendapatan</td>
                        <td class="text-end">{{ number_format($report['total_pendapatan'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-section" colspan="2">Subtotal Pesanan</td>
                        <td class="text-end">{{ number_format($report['subtotal_pesanan'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Harga Asli Produk</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['harga_asli_produk'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Jumlah Pengembalian Dana ke Pembeli</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['jumlah_pengembalian_dana_ke_pembeli'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-section" colspan="2">Voucher & Subsidi</td>
                        <td class="text-end">{{ number_format($report['voucher_subsidi'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Voucher disponsor oleh Penjual</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['voucher_disponsori_penjual'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Cashback Koin disponsori Penjual</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['cashback_koin_disponsori_penjual'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Diskon Produk dari Shopee</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['diskon_produk_dari_shopee'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- 2. Total Pengeluaran -->
                    <tr class="shopee-sub-header">
                        <td colspan="2">2. Total Pengeluaran</td>
                        <td class="text-end">{{ number_format($report['total_pengeluaran'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-section" colspan="2">Total Biaya Pengiriman</td>
                        <td class="text-end">{{ number_format($report['total_biaya_pengiriman'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Ongkir Dibayar Pembeli</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['ongkir_dibayar_pembeli'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Ongkir yang Diteruskan oleh Shopee ke Jasa Kirim</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['ongkir_diteruskan_oleh_shopee'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Diskon Ongkir Ditanggung Jasa Kirim</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['diskon_ongkir_ditanggung_jasa_kirim'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Gratis Ongkir dari Shopee</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['gratis_ongkir_dari_shopee'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Ongkos Kirim Pengembalian Barang</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['ongkos_kirim_pengembalian_barang'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Kembali ke Biaya Pengiriman Pesanan</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['kembali_ke_biaya_pengiriman_pesanan'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Pengembalian Biaya Kirim</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['pengembalian_biaya_kirim'], 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr>
                        <td class="shopee-sub-section" colspan="2">Biaya Platform</td>
                        <td class="text-end">{{ number_format($report['biaya_platform'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Administrasi</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_administrasi'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Proses Pesanan</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_proses_pesanan'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="shopee-sub-section" colspan="2">Biaya Gratis Ongkir XTRA</td>
                        <td class="text-end">{{ number_format($report['biaya_gratis_ongkir_xtra'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="shopee-sub-section" colspan="2">Biaya Layanan</td>
                        <td class="text-end">{{ number_format($report['biaya_layanan'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Transaksi</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_transaksi'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Layanan Promo XTRA</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_layanan_promo_xtra'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="shopee-sub-section" colspan="2">Biaya Promosi</td>
                        <td class="text-end">{{ number_format($report['biaya_promosi'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Kampanye</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_kampanye'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Komisi AMS</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_komisi_ams'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Isi Saldo Otomatis (dari Penghasilan)</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_isi_saldo_otomatis'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="shopee-sub-section" colspan="2">Biaya Lainnya</td>
                        <td class="text-end">{{ number_format($report['biaya_lainnya'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Program Hemat Biaya Kirim</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_program_hemat_biaya_kirim'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Premi</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['premi'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Program Shopee Live Xtra</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_program_shopee_live_xtra'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Layanan Gratis Ongkir XTRA</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_layanan_gratis_ongkir_xtra'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Layanan Pre-Order Shopee Mall</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_layanan_pre_order_shopee_mall'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">Biaya Layanan Pre-Order Non-Shopee Mall</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['biaya_layanan_pre_order_non_shopee_mall'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="shopee-sub-section" colspan="2">Pajak</td>
                        <td class="text-end">{{ number_format($report['pajak'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="shopee-sub-sub-section">PPh 22</td>
                        <td></td>
                        <td class="text-end">{{ number_format($report['pph_22'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- 3. Total yang Dilepas -->
                    <tr class="shopee-total">
                        <td colspan="2">3. Total yang Dilepas</td>
                        <td class="text-end">{{ number_format($report['total_income'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- Nilai Lainnya -->
                    <tr class="shopee-total" style="background-color: #9e9e9e; color: white;">
                        <td colspan="3">Nilai Lainnya</td>
                    </tr>
                    <tr>
                        <td colspan="2">Promo Gratis Ongkir dari Penjual</td>
                        <td class="text-end">{{ number_format($report['promo_gratis_ongkir_dari_penjual'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
