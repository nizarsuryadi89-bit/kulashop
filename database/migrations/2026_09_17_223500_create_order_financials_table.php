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
        Schema::create('order_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            $table->decimal('product_amount', 15, 2)->default(0);
            $table->decimal('shipping_paid_by_buyer', 15, 2)->default(0);

            $table->decimal('discount_seller', 15, 2)->default(0);
            $table->decimal('discount_marketplace', 15, 2)->default(0);

            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('order_processing_fee', 15, 2)->default(0);
            $table->decimal('transaction_fee', 15, 2)->default(0);
            $table->decimal('service_fee', 15, 2)->default(0);
            $table->decimal('promotion_fee', 15, 2)->default(0);
            $table->decimal('affiliate_fee', 15, 2)->default(0);
            $table->decimal('other_fee', 15, 2)->default(0);

            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('total_payment', 15, 2)->default(0);
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('settlement_amount', 15, 2)->default(0);

            $table->datetime('released_at')->nullable();

            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_financials');
    }
};
