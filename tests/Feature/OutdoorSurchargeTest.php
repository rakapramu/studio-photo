<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutdoorSurchargeTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default package
        $this->package = Package::create([
            'name' => 'Couple Portrait Session',
            'description' => 'A nice session',
            'price' => 1500000.00,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);

        // Mock config values
        config([
            'services.studio.fuel_cost_per_km' => 2000,
            'services.studio.accommodation_per_night' => 500000,
            'services.studio.latitude' => -6.597629,
            'services.studio.longitude' => 106.799568,
        ]);
    }

    /**
     * Test Indoor Session Booking doesn't add any surcharge.
     */
    public function test_indoor_session_has_no_surcharge(): void
    {
        $payload = [
            'package_id' => $this->package->id,
            'client_name' => 'Budi Client',
            'client_email' => 'budi@client.com',
            'client_phone' => '081234567890',
            'booking_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '10:00',
            'location' => 'Studio Utama Photo Raka',
            'notes' => 'Some concept notes',
            'is_outdoor' => false,
        ];

        $response = $this->post('/booking', $payload);

        $response->assertRedirect('/booking/success');

        $this->assertDatabaseHas('bookings', [
            'client_name' => 'Budi Client',
            'is_outdoor' => false,
            'travel_surcharge' => 0.00,
            'total_price' => 1500000.00,
        ]);
    }

    /**
     * Test Outdoor Session recalculates fuel, accommodation, and sets total price.
     */
    public function test_outdoor_session_recalculates_surcharge_securely(): void
    {
        $payload = [
            'package_id' => $this->package->id,
            'client_name' => 'Alice Outdoor',
            'client_email' => 'alice@outdoor.com',
            'client_phone' => '089876543210',
            'booking_date' => date('Y-m-d', strtotime('+3 days')),
            'start_time' => '13:00',
            'location' => 'Kebun Raya Bogor',
            'notes' => 'Outdoor garden session',
            'is_outdoor' => true,
            'travel_distance' => 15.5, // 15.5 km
            'fuel_cost' => 999999, // Tampered cost (should be recalculated to 15.5 * 2 * 2000 = 62,000)
            'toll_cost' => 20000, // Rp 20.000
            'accommodation_cost' => 999999, // Tampered cost (overnight is true, so should be 500,000)
            'travel_surcharge' => 999999,
            'is_overnight' => true,
            'location_latitude' => -6.597629,
            'location_longitude' => 106.799568,
        ];

        $response = $this->post('/booking', $payload);

        $response->assertRedirect('/booking/success');

        // Recalculated values:
        // fuel_cost: 15.5 * 2 * 2000 = 62,000
        // accommodation_cost: 500,000
        // toll_cost: 20,000
        // travel_surcharge: 62,000 + 20,000 + 500,000 = 582,000
        // total_price: 1,500,000 + 582,000 = 2,082,000
        $this->assertDatabaseHas('bookings', [
            'client_name' => 'Alice Outdoor',
            'is_outdoor' => true,
            'travel_distance' => 15.50,
            'fuel_cost' => 62000.00,
            'toll_cost' => 20000.00,
            'accommodation_cost' => 500000.00,
            'travel_surcharge' => 582000.00,
            'is_overnight' => true,
            'total_price' => 2082000.00,
        ]);
    }

    /**
     * Test Contract generation contains surcharge details for outdoor sessions.
     */
    public function test_contract_generation_contains_outdoor_surcharge_info(): void
    {
        $booking = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Bob Outdoor',
            'client_email' => 'bob@outdoor.com',
            'client_phone' => '087777777777',
            'booking_date' => date('Y-m-d', strtotime('+4 days')),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'is_outdoor' => true,
            'travel_distance' => 10.0,
            'fuel_cost' => 40000.00, // 10 * 2 * 2000
            'toll_cost' => 10000.00,
            'accommodation_cost' => 0.00,
            'travel_surcharge' => 50000.00,
            'is_overnight' => false,
            'total_price' => 1550000.00, // 1,500,000 + 50,000
            'location' => 'Bogor Botanical Gardens',
        ]);

        $hash = md5($booking->id . $booking->client_email . config('app.key'));

        $response = $this->get("/contract/{$booking->id}/{$hash}");
        $response->assertStatus(200);

        // Fetch contract from DB
        $contract = Contract::where('booking_id', $booking->id)->firstOrFail();

        $this->assertStringContainsString('Tipe Sesi: Outdoor (Luar Studio)', $contract->contract_text);
        $this->assertStringContainsString('Biaya Tambahan Perjalanan: Rp 50.000', $contract->contract_text);
        $this->assertStringContainsString('Jarak Tempuh: 10.00 KM', $contract->contract_text);
    }
}
