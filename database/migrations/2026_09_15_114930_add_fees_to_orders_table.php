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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('marketplace_fee', 15, 2)->default(0)->after('grand_total');
            $table->decimal('shipping_fee', 15, 2)->default(0)->after('marketplace_fee');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['marketplace_fee', 'shipping_fee']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
