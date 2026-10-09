<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('name', 'product_name');
            $table->renameColumn('variation_name', 'variant_name');
            $table->renameColumn('base_price', 'hpp');
        });

        Schema::table('products', function (Blueprint $table) {
            // Add new columns
            $table->unsignedBigInteger('category_id')->nullable()->after('product_name');
            $table->string('unit')->nullable()->after('category_id');
            $table->decimal('selling_price', 15, 2)->default(0)->after('hpp');
            $table->string('status')->default('active')->after('minimum_stock');
        });

        // Data migration: set selling_price to equal hpp initially
        DB::table('products')->update([
            'selling_price' => DB::raw('hpp'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            // Ensure sku is unique
            // If there's an existing index, we might want to check, but let's assume no unique index yet or we can just add it.
            // Check if sku unique exists to prevent crash
            $indexes = Schema::getIndexes('products');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index['name'] === 'products_sku_unique') {
                    $hasIndex = true;
                    break;
                }
            }
            if (! $hasIndex) {
                $table->unique('sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $indexes = Schema::getIndexes('products');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index['name'] === 'products_sku_unique') {
                    $hasIndex = true;
                    break;
                }
            }
            if ($hasIndex) {
                $table->dropUnique(['sku']);
            }

            $table->dropColumn(['category_id', 'unit', 'selling_price', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_name', 'name');
            $table->renameColumn('variant_name', 'variation_name');
            $table->renameColumn('hpp', 'base_price');
        });
    }
};
