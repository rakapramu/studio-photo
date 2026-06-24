<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin dashboard endpoint.
     */
    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_dashboard_returns_correct_inertia_data(): void
    {
        $admin = User::factory()->create();

        $package = Package::create([
            'name' => 'Package for Dash',
            'description' => 'Desc',
            'price' => 200000,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'package_id' => $package->id,
            'client_name' => 'John Dash',
            'client_email' => 'dash@john.com',
            'client_phone' => '0811122233',
            'booking_date' => '2026-06-24',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'confirmed',
            'total_price' => 200000,
            'paid_amount' => 0,
            'location' => 'Studio A',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);

        // Assert Inertia response keys
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
            ->has('recentBookings')
            ->has('allBookings')
            ->where('stats.totalBookings', 1)
            ->where('stats.totalPackages', 1)
            ->where('stats.pendingBookings', 0)
            ->where('stats.confirmedBookings', 1)
        );
    }
}
