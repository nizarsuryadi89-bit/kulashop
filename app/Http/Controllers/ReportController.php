<?php

namespace App\Http\Controllers;

use App\Models\Marketplace;
use App\Models\Order;
use App\Services\HppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected HppService $hppService;

    public function __construct(HppService $hppService)
    {
        $this->hppService = $hppService;
    }

    public function hppSummary(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $status = $request->input('status', '');
        $search = $request->input('search', '');

        $kpiQuery = $this->hppService->getHppKpiQuery($startDate, $endDate, $status, $search);

        $totalQty = $kpiQuery->sum('order_items.qty');

        // Sum total HPP using raw SQL to handle qty * snapshot efficiently
        $totalHpp = clone $kpiQuery;
        $totalHpp = $totalHpp->sum(DB::raw('COALESCE(order_items.hpp_snapshot, 0) * order_items.qty'));

        $itemsQuery = $this->hppService->calculateProductHppSummary($startDate, $endDate, $status, $search);

        // Count distinct items without HPP for KPI
        $itemsWithoutHpp = $this->hppService->getProductsWithoutHpp($startDate, $endDate, $status, $search);

        $items = $itemsQuery->paginate(50)->withQueryString();

        return view('report.hpp', compact(
            'items',
            'totalQty',
            'totalHpp',
            'itemsWithoutHpp',
            'startDate',
            'endDate',
            'status',
            'search'
        ));
    }

    public function syncHpp(Request $request)
    {
        $month = $request->input('month');
        if (! $month) {
            return redirect()->back()->with('error', 'Bulan tidak valid.');
        }

        $updatedCount = $this->hppService->syncMissingHpp($month);

        return redirect()->back()->with('success', "Proses Sinkronisasi HPP Selesai! Sebanyak {$updatedCount} order item berhasil dilengkapi snapshot-nya.");
    }

    public function financial(Request $request)
    {
        $month = $request->has('month') ? $request->month : date('Y-m');

        // Fetch marketplaces with their orders, items and financial data
        $marketplaces = Marketplace::with(['orders' => function ($q) use ($month) {
            $q->where('order_status', 'COMPLETED')
                ->where('order_date', 'like', "{$month}%")
                ->with(['financial', 'items']);
        }])->get();

        // Calculate custom totals and overall grand totals
        $totals = [
            'orders_count' => 0,
            'grand_total' => 0,          // Total Gross (Subtotal produk)
            'shipping_fee' => 0,
            'admin_fee' => 0,
            'tax_amount' => 0,
            'net_revenue' => 0,          // Total Penghasilan Bersih
            'total_hpp' => 0,            // Total HPP (Modal)
            'net_profit' => 0,           // Total Laba Bersih
            'total_voucher_seller' => 0, // Total Voucher Seller
            'total_promo_xtra' => 0,     // Total Biaya Layanan Promo Xtra
        ];

        foreach ($marketplaces as $mp) {
            $mp->orders_count = $mp->orders->count();

            $totalGross = 0;
            $totalShipping = 0;
            $totalAdmin = 0;
            $totalVoucher = 0;
            $totalPromoXtra = 0;
            $totalNetRevenue = 0;
            $totalHpp = 0;

            foreach ($mp->orders as $order) {
                // Calculate HPP
                foreach ($order->items as $item) {
                    $totalHpp += ($item->qty * $item->hpp_snapshot);
                }

                // If financial data exists, use it
                if ($order->financial) {
                    $totalGross += $order->financial->product_amount ?? 0;
                    $totalShipping += $order->financial->shipping_paid_by_buyer ?? 0;
                    // Admin fee is usually negative, so we can absolute it or keep it negative
                    $totalAdmin += abs($order->financial->admin_fee ?? 0);
                    $totalVoucher += abs($order->financial->discount_seller ?? 0);
                    $totalNetRevenue += $order->financial->total_income ?? 0;

                    // Extract Promo Xtra specifically from raw_data if available
                    $rawData = $order->financial->raw_data;
                    if (is_array($rawData)) {
                        $promoXtra = $rawData['biaya_layanan_promo_xtra'] ?? $rawData['biaya_layanan_promo_xtr'] ?? 0;
                        // Replace common formatting if it's string, though we already stored it as is
                        if (is_string($promoXtra)) {
                            $promoXtra = floatval(str_replace(['Rp', '.', ',', ' '], '', $promoXtra));
                        }
                        $totalPromoXtra += abs($promoXtra);
                    }
                } else {
                    // Fallback to order basic data if financial data is not imported yet
                    $totalGross += $order->total_payment;
                    $totalShipping += $order->shipping_fee;
                    $totalAdmin += $order->marketplace_fee;
                }
            }

            $mp->total_gross = $totalGross;
            $mp->total_shipping = $totalShipping;
            $mp->total_admin = $totalAdmin;

            // Tax
            $mp->total_tax = ($mp->total_gross * $mp->tax_percentage) / 100;

            // If we have financial data, net_revenue is already calculated accurately from Shopee
            // If not, we fall back to manual estimation
            $hasFinancial = $mp->orders->filter(function ($o) {
                return $o->financial !== null;
            })->count() > 0;

            if ($hasFinancial) {
                $mp->net_revenue = $totalNetRevenue;
            } else {
                $mp->net_revenue = $mp->total_gross - $mp->total_admin - $mp->total_tax;
            }

            $mp->total_voucher_seller = $totalVoucher;
            $mp->total_promo_xtra = $totalPromoXtra;
            $mp->total_hpp = $totalHpp;
            $mp->net_profit = $mp->net_revenue - $totalHpp;

            // Add to grand totals
            $totals['orders_count'] += $mp->orders_count;
            $totals['grand_total'] += $mp->total_gross;
            $totals['shipping_fee'] += $mp->total_shipping;
            $totals['admin_fee'] += $mp->total_admin;
            $totals['tax_amount'] += $mp->total_tax;
            $totals['net_revenue'] += $mp->net_revenue;
            $totals['total_hpp'] += $mp->total_hpp;
            $totals['net_profit'] += $mp->net_profit;
            $totals['total_voucher_seller'] += $mp->total_voucher_seller;
            $totals['total_promo_xtra'] += $mp->total_promo_xtra;
        }

        return view('report.financial', compact('marketplaces', 'month', 'totals'));
    }

    public function shopeeIncome(Request $request, Marketplace $marketplace)
    {
        $month = $request->has('month') ? $request->month : date('Y-m');

        // Verify this is a Shopee marketplace
        if (strtolower($marketplace->code) !== 'shopee' && ! str_contains(strtolower($marketplace->name), 'shopee')) {
            return redirect()->back()->with('error', 'Laporan Rincian Shopee hanya berlaku untuk akun Shopee.');
        }

        $orders = $marketplace->orders()
            ->where('order_status', 'COMPLETED')
            ->where('order_date', 'like', "{$month}%")
            ->with('financial')
            ->get();

        $report = [
            'total_income' => 0, // Total yang Dilepas

            // 1. Total Pendapatan
            'subtotal_pesanan' => 0,
            'harga_asli_produk' => 0,
            'jumlah_pengembalian_dana_ke_pembeli' => 0,

            'voucher_subsidi' => 0,
            'voucher_disponsori_penjual' => 0,
            'cashback_koin_disponsori_penjual' => 0,
            'diskon_produk_dari_shopee' => 0,

            // 2. Total Pengeluaran
            'total_biaya_pengiriman' => 0,
            'ongkir_dibayar_pembeli' => 0,
            'ongkir_diteruskan_oleh_shopee' => 0,
            'diskon_ongkir_ditanggung_jasa_kirim' => 0,
            'gratis_ongkir_dari_shopee' => 0,
            'ongkos_kirim_pengembalian_barang' => 0,
            'kembali_ke_biaya_pengiriman_pesanan' => 0,
            'pengembalian_biaya_kirim' => 0,

            'biaya_platform' => 0,
            'biaya_administrasi' => 0,
            'biaya_proses_pesanan' => 0,

            'biaya_gratis_ongkir_xtra' => 0,

            'biaya_layanan' => 0,
            'biaya_transaksi' => 0,
            'biaya_layanan_promo_xtra' => 0,

            'biaya_promosi' => 0,
            'biaya_kampanye' => 0,
            'biaya_komisi_ams' => 0,
            'biaya_isi_saldo_otomatis' => 0,

            'biaya_lainnya' => 0,
            'biaya_program_hemat_biaya_kirim' => 0,
            'premi' => 0,
            'biaya_program_shopee_live_xtra' => 0,
            'biaya_layanan_gratis_ongkir_xtra' => 0,
            'biaya_layanan_pre_order_shopee_mall' => 0,
            'biaya_layanan_pre_order_non_shopee_mall' => 0,

            'pajak' => 0,
            'pph_22' => 0,

            'nilai_lainnya' => 0,
            'promo_gratis_ongkir_dari_penjual' => 0,
        ];

        foreach ($orders as $order) {
            $fin = $order->financial;
            if (! $fin) {
                continue;
            }

            $raw = $fin->raw_data;
            if (! is_array($raw)) {
                continue;
            }

            $getVal = function ($keys) use ($raw) {
                if (! is_array($keys)) {
                    $keys = [$keys];
                }
                foreach ($keys as $k) {
                    if (isset($raw[$k])) {
                        $val = $raw[$k];
                        if (is_string($val)) {
                            return floatval(str_replace(['Rp', '.', ',', ' '], '', $val));
                        }

                        return (float) $val;
                    }
                }

                return 0.0;
            };

            // 1. Total Pendapatan
            $harga_asli = $getVal(['harga_asli_produk', 'harga_produk']);
            $pengembalian_dana = $getVal(['jumlah_pengembalian_dana_ke_pembeli']);
            $report['harga_asli_produk'] += $harga_asli;
            $report['jumlah_pengembalian_dana_ke_pembeli'] += $pengembalian_dana;

            $voucher_penjual = $getVal(['voucher_disponsor_oleh_penjual', 'diskon_dari_penjual', 'diskon_produk_dari_penjual']);
            $cashback_koin = $getVal(['cashback_koin_disponsori_penjual', 'cashback_koin_yang_disponsori_penjual']);
            $diskon_shopee = $getVal(['diskon_produk_dari_shopee', 'voucher_disponsor_oleh_shopee']);

            $report['voucher_disponsori_penjual'] += $voucher_penjual;
            $report['cashback_koin_disponsori_penjual'] += $cashback_koin;
            $report['diskon_produk_dari_shopee'] += $diskon_shopee;

            // 2. Total Pengeluaran
            // Ongkir
            $report['ongkir_dibayar_pembeli'] += $getVal(['ongkos_kirim_dibayar_oleh_pembeli', 'ongkir_dibayar_pembeli']);
            $report['ongkir_diteruskan_oleh_shopee'] += $getVal(['ongkir_yang_diteruskan_oleh_shopee_ke_jasa_kirim', 'ongkos_kirim_yang_dibayarkan_ke_jasa_kirim']);
            $report['diskon_ongkir_ditanggung_jasa_kirim'] += $getVal(['diskon_ongkir_ditanggung_jasa_kirim', 'potongan_ongkos_kirim_dari_jasa_kirim']);
            $report['gratis_ongkir_dari_shopee'] += $getVal(['gratis_ongkir_dari_shopee', 'diskon_ongkir_dari_shopee']);
            $report['ongkos_kirim_pengembalian_barang'] += $getVal(['ongkos_kirim_pengembalian_barang']);
            $report['kembali_ke_biaya_pengiriman_pesanan'] += $getVal(['kembali_ke_biaya_pengiriman_pesanan', 'return_to_seller_fee']);
            $report['pengembalian_biaya_kirim'] += $getVal(['pengembalian_biaya_kirim']);

            // Platform
            $report['biaya_administrasi'] += $getVal(['biaya_administrasi']);
            $report['biaya_proses_pesanan'] += $getVal(['biaya_proses_pesanan']);

            $report['biaya_gratis_ongkir_xtra'] += $getVal(['biaya_gratis_ongkir_xtra']);

            // Layanan
            $report['biaya_transaksi'] += $getVal(['biaya_transaksi']);
            $report['biaya_layanan_promo_xtra'] += $getVal(['biaya_layanan_promo_xtra', 'biaya_layanan_promo_xtr']);

            // Promosi
            $report['biaya_kampanye'] += $getVal(['biaya_kampanye']);
            $report['biaya_komisi_ams'] += $getVal(['biaya_komisi_ams']);
            $report['biaya_isi_saldo_otomatis'] += $getVal(['biaya_isi_saldo_otomatis_dari_penghasilan']);

            // Lainnya
            $report['biaya_program_hemat_biaya_kirim'] += $getVal(['biaya_program_hemat_biaya_kirim']);
            $report['premi'] += $getVal(['premi']);
            $report['biaya_program_shopee_live_xtra'] += $getVal(['biaya_program_shopee_live_xtra']);
            $report['biaya_layanan_gratis_ongkir_xtra'] += $getVal(['biaya_layanan_gratis_ongkir_xtra']);
            $report['biaya_layanan_pre_order_shopee_mall'] += $getVal(['biaya_layanan_pre_order_shopee_mall', 'biaya_layanan_pre_order_shopee_video']);
            $report['biaya_layanan_pre_order_non_shopee_mall'] += $getVal(['biaya_layanan_pre_order_non_shopee_mall', 'biaya_layanan_pre_order_non_shopee_video']);

            // Pajak
            $report['pph_22'] += $getVal(['pph_22']);

            // Nilai Lainnya
            $report['promo_gratis_ongkir_dari_penjual'] += $getVal(['promo_gratis_ongkir_dari_penjual']);
        }

        // Calculate Subtotals
        $report['subtotal_pesanan'] = $report['harga_asli_produk'] + $report['jumlah_pengembalian_dana_ke_pembeli'];
        $report['voucher_subsidi'] = $report['voucher_disponsori_penjual'] + $report['cashback_koin_disponsori_penjual'] + $report['diskon_produk_dari_shopee'];

        $report['total_biaya_pengiriman'] = $report['ongkir_dibayar_pembeli'] + $report['ongkir_diteruskan_oleh_shopee']
            + $report['diskon_ongkir_ditanggung_jasa_kirim'] + $report['gratis_ongkir_dari_shopee']
            + $report['ongkos_kirim_pengembalian_barang'] + $report['kembali_ke_biaya_pengiriman_pesanan']
            + $report['pengembalian_biaya_kirim'];

        $report['biaya_platform'] = $report['biaya_administrasi'] + $report['biaya_proses_pesanan'];

        $report['biaya_layanan'] = $report['biaya_transaksi'] + $report['biaya_layanan_promo_xtra'];

        $report['biaya_promosi'] = $report['biaya_kampanye'] + $report['biaya_komisi_ams'] + $report['biaya_isi_saldo_otomatis'];

        $report['biaya_lainnya'] = $report['biaya_program_hemat_biaya_kirim'] + $report['premi']
            + $report['biaya_program_shopee_live_xtra'] + $report['biaya_layanan_gratis_ongkir_xtra']
            + $report['biaya_layanan_pre_order_shopee_mall'] + $report['biaya_layanan_pre_order_non_shopee_mall'];

        $report['pajak'] = $report['pph_22'];

        $report['total_pendapatan'] = $report['subtotal_pesanan'] + $report['voucher_subsidi'];
        $report['total_pengeluaran'] = $report['total_biaya_pengiriman'] + $report['biaya_platform']
            + $report['biaya_gratis_ongkir_xtra'] + $report['biaya_layanan'] + $report['biaya_promosi']
            + $report['biaya_lainnya'] + $report['pajak'];

        $report['total_income'] = $report['total_pendapatan'] + $report['total_pengeluaran'];

        return view('report.shopee_income', compact('marketplace', 'month', 'report'));
    }

    public function annual(Request $request)
    {
        $selectedYear = (int) $request->input('year', date('Y'));

        // Get all years that have data
        $availableYears = Order::selectRaw('YEAR(order_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        // Monthly breakdown: pendapatan, HPP, laba bersih
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStr = sprintf('%04d-%02d', $selectedYear, $m);

            $orders = Order::where('order_status', 'COMPLETED')
                ->where('order_date', 'like', "{$monthStr}%")
                ->with(['items', 'financial'])
                ->get();

            $grossRevenue = 0;
            $netRevenue = 0;
            $totalHpp = 0;
            $ordersCount = $orders->count();
            $hasFinancial = false;

            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $totalHpp += ($item->qty * ($item->hpp_snapshot ?? 0));
                }

                if ($order->financial) {
                    $hasFinancial = true;
                    $grossRevenue += $order->financial->product_amount ?? 0;
                    $netRevenue += $order->financial->total_income ?? 0;
                } else {
                    $grossRevenue += $order->total_payment ?? 0;
                    $netRevenue += $order->total_payment ?? 0;
                }
            }

            $netProfit = $netRevenue - $totalHpp;

            $monthlyData[$m] = [
                'month_label' => Carbon::create($selectedYear, $m, 1)->translatedFormat('M'),
                'orders_count' => $ordersCount,
                'gross_revenue' => $grossRevenue,
                'net_revenue' => $netRevenue,
                'total_hpp' => $totalHpp,
                'net_profit' => $netProfit,
                'has_financial' => $hasFinancial,
            ];
        }

        // Marketplace breakdown per tahun
        $marketplaces = Marketplace::with(['orders' => function ($q) use ($selectedYear) {
            $q->where('order_status', 'COMPLETED')
                ->whereYear('order_date', $selectedYear)
                ->with(['items', 'financial']);
        }])->get();

        foreach ($marketplaces as $mp) {
            $mpGross = 0;
            $mpNet = 0;
            $mpHpp = 0;
            $mpOrders = $mp->orders->count();
            $mpFinancial = false;

            foreach ($mp->orders as $order) {
                foreach ($order->items as $item) {
                    $mpHpp += ($item->qty * ($item->hpp_snapshot ?? 0));
                }
                if ($order->financial) {
                    $mpFinancial = true;
                    $mpGross += $order->financial->product_amount ?? 0;
                    $mpNet += $order->financial->total_income ?? 0;
                } else {
                    $mpGross += $order->total_payment ?? 0;
                    $mpNet += $order->total_payment ?? 0;
                }
            }

            $mp->yearly_orders_count = $mpOrders;
            $mp->yearly_gross = $mpGross;
            $mp->yearly_net = $mpNet;
            $mp->yearly_hpp = $mpHpp;
            $mp->yearly_profit = $mpNet - $mpHpp;
            $mp->yearly_has_financial = $mpFinancial;
        }

        // Grand totals for the year
        $grandTotals = [
            'orders_count' => collect($monthlyData)->sum('orders_count'),
            'gross_revenue' => collect($monthlyData)->sum('gross_revenue'),
            'net_revenue' => collect($monthlyData)->sum('net_revenue'),
            'total_hpp' => collect($monthlyData)->sum('total_hpp'),
            'net_profit' => collect($monthlyData)->sum('net_profit'),
        ];

        return view('report.annual', compact(
            'selectedYear',
            'availableYears',
            'monthlyData',
            'marketplaces',
            'grandTotals'
        ));
    }
}
