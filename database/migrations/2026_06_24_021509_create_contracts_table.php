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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->longText('contract_text'); // Syarat & Ketentuan (SPK) saat kontrak dibuat
            $table->string('signature_path')->nullable(); // Path gambar coretan tanda tangan klien
            $table->timestamp('signed_at')->nullable(); // Waktu penandatanganan
            $table->string('ip_address')->nullable(); // Log IP client untuk kekuatan hukum e-signature
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
