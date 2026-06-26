<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Crew;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewKanbanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guests cannot access the crews kanban board.
     */
    public function test_guest_cannot_access_crews_kanban(): void
    {
        $response = $this->get(route('admin.crews.kanban'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can access the crews kanban board.
     */
    public function test_admin_can_access_crews_kanban(): void
    {
        $admin = User::factory()->create();

        // Create package and crew
        $package = Package::create([
            'name' => 'Couple Portrait Session',
            'description' => 'A nice session',
            'price' => 1500000.00,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);

        $crew = Crew::create([
            'name' => 'Kru Test',
            'role' => 'fotografer',
            'phone' => '081234567890',
            'email' => 'kru@test.com',
            'is_active' => true,
            'fee_per_session' => 200000,
        ]);

        // Create booking and assign crew
        $booking = Booking::create([
            'package_id' => $package->id,
            'client_name' => 'Client Test',
            'client_email' => 'client@test.com',
            'client_phone' => '081234567890',
            'booking_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'location' => 'Bogor',
            'total_price' => 1500000.00,
            'status' => 'confirmed',
        ]);

        $booking->crews()->attach($crew->id);

        $response = $this->actingAs($admin)->get(route('admin.crews.kanban'));

        $response->assertStatus(200);
        $response->assertSee($crew->name);
        $response->assertSee($booking->client_name);
    }
}
