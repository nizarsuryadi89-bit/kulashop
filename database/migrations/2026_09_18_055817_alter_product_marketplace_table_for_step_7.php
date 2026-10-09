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
            $table->string('marketplace_product_id')->nullable()->after('marketplace_account_id');
            $table->string('marketplace_product_name')->nullable()->after('marketplace_sku');
            $table->string('marketplace_variant')->nullable()->after('marketplace_product_name');

            // Add index for marketplace_sku
            $table->index('marketplace_sku');

            // Add unique constraint for marketplace_account_id + marketplace_sku with short name
            $table->unique(['marketplace_account_id', 'marketplace_sku'], 'pm_acc_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_marketplace', function (Blueprint $table) {
            $table->dropUnique('pm_acc_sku_unique');
            $table->dropIndex(['marketplace_sku']);

            $table->dropColumn([
                'marketplace_product_id',
                'marketplace_product_name',
                'marketplace_variant',
            ]);
        });
    }
};
