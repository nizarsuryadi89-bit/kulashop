<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMarketplace;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Facades\Excel;

class ShopeeOrderImportService
{
    /**
     * Proses preview file excel untuk ditampilkan di frontend
     * tanpa menyimpan ke database
     */
    public function processPreview(string $filePath, string $originalFileName, $marketplaceAccountId): array
    {
        // Baca data untuk preview (maksimal 10 baris pertama untuk performa)
        $importObj = new class implements Import {};
        $data = Excel::toArray($importObj, $filePath, 'private');

        if (empty($data) || empty($data[0])) {
            throw new \Exception('File kosong atau format tidak didukung.');
        }

        $sheetData = $data[0]; // Ambil sheet pertama
        $headers = $sheetData[0] ?? [];

        if (count($sheetData) <= 1) {
            throw new \Exception('File Excel tidak memiliki data transaksi (kosong).');
        }

        $requiredHeaders = [
            'No. Pesanan',
            'Status Pesanan',
            'Nama Produk',
            'Jumlah',
            'Harga Setelah Diskon',
            'Total Pembayaran',
            'Username (Pembeli)',
            'Nama Penerima',
            'Alamat Pengiriman',
            'Kota/Kabupaten',
            'Provinsi',
            'Waktu Pesanan Dibuat',
        ];

        $missingHeaders = [];
        // Clean headers (trim whitespace) for safer comparison
        $cleanHeaders = array_map('trim', $headers);

        foreach ($requiredHeaders as $req) {
            if (! in_array($req, $cleanHeaders)) {
                $missingHeaders[] = $req;
            }
        }

        if (! empty($missingHeaders)) {
            throw new \Exception('Format file Shopee tidak sesuai. Beberapa kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders));
        }

        // Create header map
        $headerMap = array_flip($cleanHeaders);

        $orderIds = [];
        $skus = [];

        $colOrderNo = $headerMap['No. Pesanan'] ?? -1;
        $colSku = $headerMap['Nomor Referensi SKU'] ?? ($headerMap['SKU Induk'] ?? -1);
        $colProduk = $headerMap['Nama Produk'] ?? -1;
        $colQty = $headerMap['Jumlah'] ?? -1;
        $colHarga = $headerMap['Harga Setelah Diskon'] ?? -1;

        $totalRows = count($sheetData) - 1;
        $allRows = array_slice($sheetData, 1);

        foreach ($allRows as $row) {
            if ($colOrderNo !== -1 && ! empty($row[$colOrderNo])) {
                $orderIds[] = (string) $row[$colOrderNo];
            }
            if ($colSku !== -1 && ! empty($row[$colSku]) && $row[$colSku] !== '-') {
                $skus[] = (string) $row[$colSku];
            }
        }

        $uniqueOrderIds = array_unique($orderIds);
        $uniqueSkus = array_unique($skus);

        $existingOrders = Order::where('marketplace_account_id', $marketplaceAccountId)
            ->whereIn('external_order_id', $uniqueOrderIds)
            ->pluck('external_order_id')
            ->toArray();

        // For SKUs, first check product_marketplace mapping, then master products
        // For preview summary, we just check how many of the unique SKUs actually have a mapping or exist
        $mappedSkus = ProductMarketplace::where('marketplace_account_id', $marketplaceAccountId)
            ->whereIn('marketplace_sku', $uniqueSkus)
            ->pluck('marketplace_sku')
            ->toArray();

        // Remaining unmapped SKUs might exist in master
        $remainingSkus = array_diff($uniqueSkus, $mappedSkus);
        $existingSkus = Product::whereIn('sku', $remainingSkus)
            ->pluck('sku')
            ->toArray();

        $totalExistingSkus = count($mappedSkus) + count($existingSkus);
        $newSkusCount = count($uniqueSkus) - $totalExistingSkus;

        $existingOrdersCount = count($existingOrders);
        $newOrdersCount = count($uniqueOrderIds) - $existingOrdersCount;

        $existingSkusCount = count($existingSkus);
        $newSkusCount = count($uniqueSkus) - $existingSkusCount;

        $previewRows = [];
        $limit = 10;
        $count = 0;

        foreach ($allRows as $row) {
            if ($count >= $limit) {
                break;
            }

            $noPesanan = $colOrderNo !== -1 ? ($row[$colOrderNo] ?? '') : '';
            if (empty($noPesanan)) {
                continue;
            } // skip empty rows

            $produk = $colProduk !== -1 ? ($row[$colProduk] ?? '') : '';
            $sku = $colSku !== -1 ? ($row[$colSku] ?? '') : '';
            $qty = $colQty !== -1 ? ($row[$colQty] ?? '') : '';
            $harga = $colHarga !== -1 ? ($row[$colHarga] ?? '') : '';

            $isNewOrder = ! in_array((string) $noPesanan, $existingOrders);
            $statusText = $isNewOrder ? 'Baru' : 'Update';

            $previewRows[] = [
                $noPesanan,
                $produk,
                $sku,
                $qty,
                $harga,
                $statusText,
            ];
            $count++;
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $originalFileName,
            'headers' => ['No Pesanan', 'Produk', 'SKU', 'Qty', 'Harga', 'Status'],
            'preview_rows' => $previewRows,
            'total_rows' => $totalRows,
            'total_unique_orders' => count($uniqueOrderIds),
            'new_orders' => $newOrdersCount,
            'updated_orders' => $existingOrdersCount,
            'unmapped_skus' => count($uniqueSkus) - $existingSkusCount,
            'errors' => 0,
        ];
    }
}
