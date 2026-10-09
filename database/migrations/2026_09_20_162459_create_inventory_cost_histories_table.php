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
        Schema::create('inventory_cost_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            
            $table->decimal('old_average_cost', 15, 4)->default(0);
            $table->decimal('new_average_cost', 15, 4)->default(0);
            
            $table->integer('old_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            
            $table->decimal('old_inventory_value', 15, 4)->default(0);
            $table->decimal('new_inventory_value', 15, 4)->default(0);
            
            $table->string('reference_type'); // opening_stock, purchase, adjustment
            $table->string('reference_id')->nullable();
            
            $table->timestamp('effective_at');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_histories');
    }
};
