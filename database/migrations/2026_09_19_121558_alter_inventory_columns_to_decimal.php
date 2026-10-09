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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 15, 3)->default(0)->change();
            $table->decimal('minimum_stock', 15, 3)->default(0)->change();
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 15, 3)->change();
            $table->decimal('previous_stock', 15, 3)->change();
            $table->decimal('current_stock', 15, 3)->change();
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
            $table->integer('minimum_stock')->default(0)->change();
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->integer('qty')->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('previous_stock')->change();
            $table->integer('current_stock')->change();
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('qty')->default(0)->change();
        });
    }
};
