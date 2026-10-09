<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFinancial;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Facades\Excel;

class ShopeeIncomeImportService
{
    /**
     * Proses preview file excel untuk ditampilkan di frontend
     * tanpa menyimpan ke database
     */
    public function processPreview(string $filePath, string $originalFileName, $marketplaceAccountId): array
    {
        // Baca data untuk preview
        $importObj = new class implements Import {};
        $data = Excel::toArray($importObj, $filePath, 'private');

        if (empty($data) || empty($data[0])) {
            throw new \Exception('File kosong atau format tidak didukung.');
        }

        // Cari sheet Penghasilan atau Income, atau ambil yang pertama
        $sheetData = isset($data['Penghasilan']) ? $data['Penghasilan'] : (isset($data['Income']) ? $data['Income'] : array_values($data)[0]);

        // Asumsikan header ada di baris ke-3 (index 2) sesuai format Shopee
        $headerRowIndex = 2;
        if (count($sheetData) <= $headerRowIndex + 1) {
            throw new \Exception('File Excel tidak memiliki data transaksi (kosong).');
        }

        $headers = $sheetData[$headerRowIndex] ?? [];
        $cleanHeaders = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '.'], ['_', ''], $h)));
        }, $headers);

        $requiredHeaders = ['no_pesanan', 'total_penghasilan']; // Minimal

        $missingHeaders = [];
        foreach ($requiredHeaders as $req) {
            $found = false;
            foreach ($cleanHeaders as $ch) {
                if ($ch === $req || $ch === 'penghasilan') {
                    $found = true;
                    break;
                }
            }
            if (! $found && $req === 'total_penghasilan') {
                $missingHeaders[] = 'total_penghasilan / penghasilan';
            } elseif (! $found) {
                $missingHeaders[] = $req;
            }
        }

        if (! empty($missingHeaders)) {
            throw new \Exception('Format file Shopee Income tidak sesuai. Beberapa kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders));
        }

        // Buat map header (slug -> index)
        $headerMap = [];
        foreach ($cleanHeaders as $index => $slug) {
            if (! empty($slug)) {
                $headerMap[$slug] = $index;
            }
        }

        $colOrderNo = $headerMap['no_pesanan'] ?? -1;
        $colLihatBerdasarkan = $headerMap['lihat_berdasarkan'] ?? -1;

        $orderIds = [];
        $errorCount = 0;

        $allRows = array_slice($sheetData, $headerRowIndex + 1);
        $totalRows = 0;

        foreach ($allRows as $row) {
            // Cek apakah ada datanya (mencegah baris kosong)
            if (empty($row) || ($colOrderNo !== -1 && empty($row[$colOrderNo]))) {
                continue;
            }

            // Skip baris SKU
            if ($colLihatBerdasarkan !== -1 && isset($row[$colLihatBerdasarkan])) {
                if (strtolower(trim($row[$colLihatBerdasarkan])) !== 'order') {
                    continue;
                }
            }

            $totalRows++;
            $orderIds[] = (string) $row[$colOrderNo];
        }

        $uniqueOrderIds = array_unique($orderIds);
        $orderUnik = count($uniqueOrderIds);

        // Cari order yang sudah ada di database
        $existingOrders = Order::where('marketplace_account_id', $marketplaceAccountId)
            ->whereIn('external_order_id', $uniqueOrderIds)
            ->pluck('id', 'external_order_id')
            ->toArray();

        $orderDitemukan = count($existingOrders);
        $orderTidakDitemukan = $orderUnik - $orderDitemukan;

        // Cari financial existing berdasarkan order id yang ditemukan
        $existingFinancialsCount = OrderFinancial::whereIn('order_id', array_values($existingOrders))
            ->count();

        $financialBaru = $orderDitemukan - $existingFinancialsCount;
        $financialExisting = $existingFinancialsCount;

        // Simulasikan error: Order Tidak Ditemukan dianggap Error dalam konteks Income import
        $error = $orderTidakDitemukan;

        return [
            'total_rows' => $totalRows,
            'order_unik' => $orderUnik,
            'order_ditemukan' => $orderDitemukan,
            'order_tidak_ditemukan' => $orderTidakDitemukan,
            'financial_existing' => $financialExisting,
            'financial_baru' => $financialBaru,
            'error_count' => $error,

            // Format HTML Response for the View
            'html' => '
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <div class="text-muted small">Total Row Valid (Order Level)</div>
                        <h4 class="mb-0 fw-bold">'.number_format($totalRows).'</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-primary bg-opacity-10 border-primary">
                        <div class="text-primary small">Order Unik</div>
                        <h4 class="mb-0 fw-bold text-primary">'.number_format($orderUnik).'</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-success bg-opacity-10 border-success">
                        <div class="text-success small">Order Ditemukan (Match)</div>
                        <h4 class="mb-0 fw-bold text-success">'.number_format($orderDitemukan).'</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-danger bg-opacity-10 border-danger">
                        <div class="text-danger small">Order Tidak Ditemukan</div>
                        <h4 class="mb-0 fw-bold text-danger">'.number_format($orderTidakDitemukan).'</h4>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="border rounded p-3 border-info">
                        <div class="text-info small">Financial Baru (Create)</div>
                        <h4 class="mb-0 fw-bold text-info">'.number_format($financialBaru).'</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 border-warning">
                        <div class="text-warning small">Financial Existing (Update)</div>
                        <h4 class="mb-0 fw-bold text-warning">'.number_format($financialExisting).'</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 border-danger">
                        <div class="text-danger small">Total Error / Ditolak</div>
                        <h4 class="mb-0 fw-bold text-danger">'.number_format($error).'</h4>
                    </div>
                </div>
            </div>
            ',
        ];
    }
}
