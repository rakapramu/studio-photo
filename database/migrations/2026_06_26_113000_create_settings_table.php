<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default configurations
        $now = now();
        DB::table('settings')->insert([
            ['key' => 'studio_name', 'value' => 'Studio Photo Raka', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'studio_address', 'value' => 'Bogor, Jawa Barat, Indonesia', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'studio_latitude', 'value' => '-6.597629', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'studio_longitude', 'value' => '106.799568', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fuel_cost_per_km', 'value' => '2000', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'accommodation_cost_per_night', 'value' => '500000', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
