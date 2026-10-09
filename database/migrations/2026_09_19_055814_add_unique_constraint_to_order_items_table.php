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
        Schema::table('order_items', function (Blueprint $table) {
            // Karena panjang string bisa melebihi limit key MySQL (terutama UTF8MB4),
            // kita beri nama index custom yang pendek dan pastikan panjangnya aman
            $table->unique(['order_id', 'product_name', 'variant_name'], 'order_items_identity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique('order_items_identity_unique');
        });
    }
};
