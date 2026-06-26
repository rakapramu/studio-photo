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
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_outdoor')->default(false)->after('location');
            $table->decimal('travel_distance', 8, 2)->default(0.00)->after('is_outdoor');
            $table->decimal('fuel_cost', 12, 2)->default(0.00)->after('travel_distance');
            $table->decimal('toll_cost', 12, 2)->default(0.00)->after('fuel_cost');
            $table->decimal('accommodation_cost', 12, 2)->default(0.00)->after('toll_cost');
            $table->decimal('travel_surcharge', 12, 2)->default(0.00)->after('accommodation_cost');
            $table->boolean('is_overnight')->default(false)->after('travel_surcharge');
            $table->decimal('location_latitude', 10, 7)->nullable()->after('is_overnight');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'is_outdoor',
                'travel_distance',
                'fuel_cost',
                'toll_cost',
                'accommodation_cost',
                'travel_surcharge',
                'is_overnight',
                'location_latitude',
                'location_longitude',
            ]);
        });
    }
};
