<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_marketplace', function (Blueprint $table) {
            // Kita batasi hanya ada satu entri yang sama per SKU di per Akun untuk mencegah conflict duplikat
            $table->unique(['marketplace_account_id', 'marketplace_sku'], 'idx_marketplace_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_marketplace', function (Blueprint $table) {
            $table->dropUnique('idx_marketplace_sku_unique');
        });
    }
};
