@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->external_order_id)

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Detail Pesanan: {{ $order->external_order_id }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('order.index') }}">Pesanan</a></li>
                <li class="breadcrumb-item active">{{ $order->external_order_id }}</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Order Info -->
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pesanan</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td width="30%"><strong>Order ID</strong></td>
                            <td>{{ $order->external_order_id }}</td>
                        </tr>
                        <tr>
                            <td><strong>Marketplace</strong></td>
                            <td>{{ $order->marketplaceAccount->account_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>
                                @if($order->order_status == 'COMPLETED')
                                    <span class="badge badge-success">COMPLETED</span>
                                @elseif(in_array($order->order_status, ['CANCELLED', 'RETURNED']))
                                    <span class="badge badge-danger">{{ $order->order_status }}</span>
                                @else
                                    <span class="badge badge-warning">{{ $order->order_status }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Order</strong></td>
                            <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d F Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Customer</strong></td>
                            <td>{{ $order->customer->customer_name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="col-md-6">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan Keuangan</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td width="40%"><strong>Total Pembayaran</strong></td>
                            <td class="text-right">Rp {{ number_format($order->total_payment, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Income Shopee</strong></td>
                            <td class="text-right">
                                @if($order->financial)
                                    Rp {{ number_format($order->financial->total_income, 0, ',', '.') }}
                                @else
                                    <span class="badge badge-warning">Belum Ada Data Income</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total HPP</strong></td>
                            <td class="text-right">
                                @if($order->total_hpp !== null)
                                    Rp {{ number_format($order->total_hpp, 0, ',', '.') }}
                                @else
                                    <span class="badge badge-danger">BELUM LENGKAP</span>
                                @endif
                            </td>
                        </tr>
                        @if(auth()->check() && auth()->user()->isOwner())
                        <tr>
                            <td><strong>Gross Profit</strong></td>
                            <td class="text-right text-bold text-success">
                                @if($order->gross_profit !== null)
                                    Rp {{ number_format($order->gross_profit, 0, ',', '.') }}
                                @else
                                    <span class="text-danger"><i class="fas fa-exclamation-circle"></i> Menunggu Data</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Gross Margin</strong></td>
                            <td class="text-right text-bold">
                                @if($order->gross_margin !== null)
                                    {{ number_format($order->gross_margin, 2, ',', '.') }}%
                                @else
                                    <span class="text-danger">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Status Profit</strong></td>
                            <td class="text-right">
                                @if($order->profit_status === 'Profit Available')
                                    <span class="badge badge-success">Profit Available</span>
                                @elseif($order->profit_status === 'Profit Incomplete')
                                    <span class="badge badge-warning text-dark">Profit Incomplete</span>
                                @else
                                    <span class="badge badge-danger">Not Available</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Rincian Item</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>SKU / Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Harga Jual</th>
                        <th class="text-right">Subtotal Jual</th>
                        <th class="text-right">HPP Snapshot</th>
                        <th class="text-right">Total HPP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->sku ?? '-' }}</strong><br>
                                {{ $item->item_name }}
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-right">Rp {{ number_format($item->selling_price, 2, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                            <td class="text-right">
                                @if($item->hpp_snapshot !== null)
                                    Rp {{ number_format($item->hpp_snapshot, 2, ',', '.') }}
                                @else
                                    <span class="badge badge-danger">KOSONG</span>
                                @endif
                            </td>
                            <td class="text-right">
                                Rp {{ number_format(($item->hpp_snapshot ?? 0) * $item->qty, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop
