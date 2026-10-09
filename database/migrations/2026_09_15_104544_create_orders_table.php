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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->string('raw_status')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_provider')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->dateTime('paid_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
