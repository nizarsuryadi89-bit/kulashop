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
            if (Schema::hasColumn('order_items', 'quantity')) {
                $table->renameColumn('quantity', 'qty');
            }
            if (Schema::hasColumn('order_items', 'price')) {
                $table->renameColumn('price', 'selling_price');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'marketplace_sku')) {
                $table->string('marketplace_sku')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('order_items', 'variant_name')) {
                $table->string('variant_name')->nullable()->after('product_name');
            }
            if (! Schema::hasColumn('order_items', 'original_price')) {
                $table->decimal('original_price', 15, 2)->default(0)->after('qty');
            }
            if (! Schema::hasColumn('order_items', 'hpp_snapshot')) {
                $table->decimal('hpp_snapshot', 15, 2)->default(0)->after('subtotal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'qty')) {
                $table->renameColumn('qty', 'quantity');
            }
            if (Schema::hasColumn('order_items', 'selling_price')) {
                $table->renameColumn('selling_price', 'price');
            }
            $table->dropColumn(['marketplace_sku', 'variant_name', 'original_price', 'hpp_snapshot']);
        });
    }
};
