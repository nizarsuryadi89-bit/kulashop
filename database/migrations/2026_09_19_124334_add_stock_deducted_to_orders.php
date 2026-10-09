<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom stock_deducted ke tabel orders.
     *
     * Kolom ini adalah sumber kebenaran tunggal (single source of truth)
     * apakah stok sudah dipotong untuk order tersebut.
     * Lebih reliable daripada mengecek stock_movements karena:
     * - Tidak terpengaruh oleh manual delete/revert pada stock_movements
     * - Mudah di-audit dan di-report
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('stock_deducted')
                ->default(false)
                ->after('cancelled_at')
                ->comment('True jika stok sudah dipotong untuk order ini. Sumber kebenaran idempotency.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_deducted');
        });
    }
};
