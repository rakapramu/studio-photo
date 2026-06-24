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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->nullable()->unique(); // ID transaksi dari Payment Gateway
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->string('type'); // down_payment, final_payment, full_payment
            $table->string('payment_method')->nullable(); // qris, bank_transfer, gopay, dll.
            $table->string('snap_token')->nullable(); // Token untuk Midtrans Snap Checkout
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
