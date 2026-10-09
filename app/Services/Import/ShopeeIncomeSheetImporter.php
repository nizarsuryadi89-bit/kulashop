<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\Order;
use App\Models\OrderFinancial;
use App\Traits\MapsImportErrors;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ShopeeIncomeSheetImporter implements ToCollection, WithChunkReading, WithEvents, WithHeadingRow
{
    public function headingRow(): int
    {
        return 3;
    }

    use MapsImportErrors;
    use RemembersRowNumber;

    protected $batch;

    public $total_rows = 0;

    public $success_rows = 0;

    public $duplicate_rows = 0;

    public $updated_rows = 0;

    public $error_rows = 0;

    public $min_date = null;

    public $max_date = null;

    public function __construct(ImportBatch $batch)
    {
        $this->batch = $batch;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $orderId = $row['no_pesanan'] ?? null;
                if (! $orderId || strtolower($orderId) === 'no. pesanan' || strtolower($orderId) === 'no_pesanan') {
                    continue;
                }

                if (isset($row['lihat_berdasarkan']) && strtolower(trim($row['lihat_berdasarkan'])) !== 'order') {
                    continue;
                }

                // Find the parent order
                $order = Order::where('marketplace_account_id', $this->batch->marketplace_account_id)
                    ->where('external_order_id', $orderId)
                    ->first();

                if (! $order) {
                    // If order not found, log to import_errors
                    $this->total_rows++;
                    $this->error_rows++;

                    $this->batch->importErrors()->create([
                        'row_number' => null, // Row number tracking inside chunk is lost
                        'external_order_id' => $orderId,
                        'error_type' => 'order_not_found',
                        'error_message' => 'Order Shopee belum ditemukan pada ERP. Import Order terlebih dahulu.',
                        'raw_data' => $row,
                    ]);

                    continue;
                }

                DB::transaction(function () use ($row, $order) {
                    // Parse numeric fields safely
                    $productAmount = $this->parseNumber($row['harga_asli_produk'] ?? $row['harga_produk'] ?? 0);
                    $shippingPaidByBuyer = $this->parseNumber($row['ongkos_kirim_dibayar_oleh_pembeli'] ?? $row['ongkir_dibayar_pembeli'] ?? 0);

                    $discountSeller = abs($this->parseNumber($row['diskon_dari_penjual'] ?? $row['diskon_produk_dari_penjual'] ?? $row['voucher_disponsor_oleh_penjual'] ?? 0));
                    $discountMarketplace = abs($this->parseNumber($row['diskon_dari_shopee'] ?? $row['diskon_produk_dari_shopee'] ?? 0))
                                         + abs($this->parseNumber($row['gratis_ongkir_dari_shopee'] ?? 0));

                    $adminFee = abs($this->parseNumber($row['biaya_administrasi'] ?? 0));
                    $serviceFee = abs($this->parseNumber($row['biaya_layanan'] ?? 0))
                                + abs($this->parseNumber($row['biaya_layanan_promo_xtr'] ?? $row['biaya_layanan_promo_xtra'] ?? 0))
                                + abs($this->parseNumber($row['biaya_layanan_gratis_ongkir_xtra'] ?? 0))
                                + abs($this->parseNumber($row['biaya_layanan_pre_order_shopee_video'] ?? 0))
                                + abs($this->parseNumber($row['biaya_layanan_pre_order_non_shopee_video'] ?? 0));
                    $transactionFee = abs($this->parseNumber($row['biaya_transaksi'] ?? 0));
                    $affiliateFee = abs($this->parseNumber($row['komisi_affiliate'] ?? $row['biaya_komisi_ams'] ?? 0));
                    $promotionFee = abs($this->parseNumber($row['biaya_promosi'] ?? $row['biaya_kampanye'] ?? 0));
                    $orderProcessingFee = abs($this->parseNumber($row['biaya_proses_pesanan'] ?? 0));

                    $otherFee = abs($this->parseNumber($row['biaya_lainnya'] ?? $row['biaya_isi_saldo_otomatis'] ?? $row['biaya_isi_saldo_otomatis_dari_penghasilan'] ?? 0));
                    $refundAmount = abs($this->parseNumber($row['pengembalian_dana_ke_pembeli'] ?? $row['pengembalian_dana'] ?? $row['jumlah_pengembalian_dana_ke_pembeli'] ?? 0));
                    $taxAmount = abs($this->parseNumber($row['pajak'] ?? $row['pph_22'] ?? 0));

                    $totalIncome = $this->parseNumber($row['total_penghasilan'] ?? $row['penghasilan'] ?? 0);
                    $jumlahDibayarPembeli = $this->parseNumber($row['jumlah_dibayar_pembeli'] ?? 0);

                    $releasedAt = null;
                    $releasedAtRaw = $row['waktu_dana_dilepaskan'] ?? $row['tanggal_dana_dilepaskan'] ?? null;
                    if (! empty($releasedAtRaw) && $releasedAtRaw !== '-') {
                        try {
                            // Check if it's an Excel Date Serial (numeric)
                            if (is_numeric($releasedAtRaw)) {
                                $releasedAt = Carbon::instance(Date::excelToDateTimeObject($releasedAtRaw));
                            } else {
                                $releasedAt = Carbon::parse($releasedAtRaw);
                            }
                        } catch (\Exception $e) {
                            throw new \Exception('invalid_date|Format tanggal dana dilepaskan tidak valid: '.$releasedAtRaw);
                        }
                    }

                    $financial = OrderFinancial::firstOrNew(['order_id' => $order->id]);
                    $isNewFinancial = ! $financial->exists;

                    if ($productAmount != 0) {
                        $financial->product_amount = $productAmount;
                    }
                    if ($shippingPaidByBuyer != 0) {
                        $financial->shipping_paid_by_buyer = $shippingPaidByBuyer;
                    }
                    if ($discountSeller != 0) {
                        $financial->discount_seller = $discountSeller;
                    }
                    if ($discountMarketplace != 0) {
                        $financial->discount_marketplace = $discountMarketplace;
                    }
                    if ($adminFee != 0) {
                        $financial->admin_fee = $adminFee;
                    }
                    if ($orderProcessingFee != 0) {
                        $financial->order_processing_fee = $orderProcessingFee;
                    }
                    if ($serviceFee != 0) {
                        $financial->service_fee = $serviceFee;
                    }
                    if ($transactionFee != 0) {
                        $financial->transaction_fee = $transactionFee;
                    }
                    if ($promotionFee != 0) {
                        $financial->promotion_fee = $promotionFee;
                    }
                    if ($affiliateFee != 0) {
                        $financial->affiliate_fee = $affiliateFee;
                    }
                    if ($otherFee != 0) {
                        $financial->other_fee = $otherFee;
                    }
                    if ($refundAmount != 0) {
                        $financial->refund_amount = $refundAmount;
                    }
                    if ($taxAmount != 0) {
                        $financial->tax_amount = $taxAmount;
                    }
                    if (isset($row['total_penghasilan']) || isset($row['penghasilan'])) {
                        $financial->total_income = $totalIncome;
                        $financial->settlement_amount = $totalIncome;
                    }
                    if ($jumlahDibayarPembeli != 0) {
                        $financial->total_payment = $jumlahDibayarPembeli;
                    }

                    if ($releasedAt) {
                        $financial->released_at = $releasedAt;

                        if (is_null($this->min_date) || $releasedAt->lt($this->min_date)) {
                            $this->min_date = clone $releasedAt;
                        }
                        if (is_null($this->max_date) || $releasedAt->gt($this->max_date)) {
                            $this->max_date = clone $releasedAt;
                        }
                    }

                    // Only update raw_data if it's the first time
                    if ($isNewFinancial) {
                        $financial->raw_data = $row;
                    }

                    // Inherited from order or read from income sheet
                    if ($jumlahDibayarPembeli != 0) {
                        $financial->total_payment = $jumlahDibayarPembeli;
                    } else {
                        $financial->total_payment = $order->total_payment;
                    }
                    $financial->save();

                    $this->total_rows++;

                    if ($isNewFinancial) {
                        $this->success_rows++;
                    } elseif ($financial->wasChanged()) {
                        $this->updated_rows++;
                    } else {
                        $this->duplicate_rows++;
                    }
                });

            } catch (\Throwable $e) {
                $this->total_rows++;
                $this->error_rows++;

                $errorType = $this->mapErrorType($e);
                $errorMessage = substr($e->getMessage(), 0, 250);

                if ($errorType === 'unknown_error' && strpos($errorMessage, '|') !== false) {
                    [$errorType, $errorMessage] = explode('|', $errorMessage, 2);
                    $errorMessage = substr($errorMessage, 0, 250);
                }

                $this->batch->importErrors()->create([
                    'row_number' => null,
                    'external_order_id' => $orderId ?? null,
                    'error_type' => $errorType,
                    'error_message' => $errorMessage,
                    'raw_data' => $row,
                ]);

                \Log::error('Shopee Income Import Row Error: '.$e->getMessage(), [
                    'import_batch_id' => $this->batch->id,
                    'row_number' => null,
                    'external_order_id' => $orderId ?? null,
                ]);
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->batch->increment('total_rows', $this->total_rows);
                $this->batch->increment('success_rows', $this->success_rows);
                $this->batch->increment('duplicate_rows', $this->duplicate_rows);
                $this->batch->increment('updated_rows', $this->updated_rows);
                $this->batch->increment('error_rows', $this->error_rows);

                // Update period if found
                if ($this->min_date || $this->max_date) {
                    $updateData = [];
                    if ($this->min_date && (is_null($this->batch->period_start) || $this->min_date->lt($this->batch->period_start))) {
                        $updateData['period_start'] = $this->min_date;
                    }
                    if ($this->max_date && (is_null($this->batch->period_end) || $this->max_date->gt($this->batch->period_end))) {
                        $updateData['period_end'] = $this->max_date;
                    }
                    if (! empty($updateData)) {
                        $this->batch->update($updateData);
                    }
                }
            },
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function parseNumber($val)
    {
        if (empty($val) || $val === '-') {
            return 0;
        }

        $val = (string) $val;
        $val = str_replace(['Rp', ' ', "\xC2\xA0"], '', $val);
        $val = str_replace(['.', ','], '', $val);

        if (! is_numeric($val) && $val !== '') {
            throw new \Exception('invalid_amount|Format nominal tidak valid: '.$val);
        }

        return (float) $val;
    }
}
