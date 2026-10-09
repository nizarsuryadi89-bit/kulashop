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
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('reference_type'); // opening_stock, purchase, adjustment, transfer_in
            $table->string('reference_id')->nullable(); 
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4);
            $table->integer('remaining_quantity');
            $table->string('status')->default('active'); // active, depleted
            $table->datetime('cost_date');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
