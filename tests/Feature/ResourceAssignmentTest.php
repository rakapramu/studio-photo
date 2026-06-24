<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Crew;
use App\Models\Equipment;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->admin = User::factory()->create();

        // Create a default package
        $this->package = Package::create([
            'name' => 'Test Studio Package',
            'description' => 'Test Package Description',
            'price' => 500000.00,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
    }

    /**
     * Test Crew CRUD operations.
     */
    public function test_crew_crud_operations(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/crews');
        $response->assertStatus(200);

        // 2. Store
        $response = $this->actingAs($this->admin)->post('/admin/crews', [
            'name' => 'John Doe',
            'role' => 'fotografer',
            'phone' => '0899999999',
            'email' => 'john@doe.com',
            'is_active' => true,
            'fee_per_session' => 150000.00,
        ]);
        $response->assertRedirect('/admin/crews');
        $this->assertDatabaseHas('crews', ['name' => 'John Doe', 'role' => 'fotografer']);

        $crew = Crew::where('name', 'John Doe')->first();

        // 3. Update
        $response = $this->actingAs($this->admin)->put("/admin/crews/{$crew->id}", [
            'name' => 'John Doe Updated',
            'role' => 'videografer',
            'phone' => '0899999999',
            'email' => 'john@doe.com',
            'is_active' => false,
            'fee_per_session' => 200000.00,
        ]);
        $response->assertRedirect('/admin/crews');
        $this->assertDatabaseHas('crews', ['name' => 'John Doe Updated', 'role' => 'videografer', 'is_active' => false]);

        // 4. Delete
        $response = $this->actingAs($this->admin)->delete("/admin/crews/{$crew->id}");
        $response->assertRedirect('/admin/crews');
        $this->assertDatabaseMissing('crews', ['id' => $crew->id]);
    }

    /**
     * Test Equipment CRUD operations.
     */
    public function test_equipment_crud_operations(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/equipments');
        $response->assertStatus(200);

        // 2. Store
        $response = $this->actingAs($this->admin)->post('/admin/equipments', [
            'name' => 'Sony A7S III',
            'type' => 'kamera',
            'serial_number' => 'SN-SONY-A7S3',
            'status' => 'active',
            'notes' => 'Continuous video focus camera',
        ]);
        $response->assertRedirect('/admin/equipments');
        $this->assertDatabaseHas('equipments', ['name' => 'Sony A7S III', 'type' => 'kamera']);

        $equipment = Equipment::where('name', 'Sony A7S III')->first();

        // 3. Update
        $response = $this->actingAs($this->admin)->put("/admin/equipments/{$equipment->id}", [
            'name' => 'Sony A7S III Updated',
            'type' => 'kamera',
            'serial_number' => 'SN-SONY-A7S3-UPDATED',
            'status' => 'maintenance',
            'notes' => 'In service checkup',
        ]);
        $response->assertRedirect('/admin/equipments');
        $this->assertDatabaseHas('equipments', ['name' => 'Sony A7S III Updated', 'status' => 'maintenance']);

        // 4. Delete
        $response = $this->actingAs($this->admin)->delete("/admin/equipments/{$equipment->id}");
        $response->assertRedirect('/admin/equipments');
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    /**
     * Test assigning resources to booking when there's no conflict.
     */
    public function test_successful_resource_assignment_no_conflict(): void
    {
        $booking = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Alice Client',
            'client_email' => 'alice@test.com',
            'client_phone' => '0812121212',
            'booking_date' => '2026-07-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
            'total_price' => 500000.00,
            'paid_amount' => 0.00,
            'location' => 'Studio A',
        ]);

        $crew = Crew::create([
            'name' => 'Raka Photographer',
            'role' => 'fotografer',
            'phone' => '0811111111',
            'is_active' => true,
        ]);

        $equipment = Equipment::create([
            'name' => 'Sony A7 IV',
            'type' => 'kamera',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/bookings/{$booking->id}/assign", [
            'crew_ids' => [$crew->id],
            'equipment_ids' => [$equipment->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('booking_crew', [
            'booking_id' => $booking->id,
            'crew_id' => $crew->id,
        ]);

        $this->assertDatabaseHas('booking_equipment', [
            'booking_id' => $booking->id,
            'equipment_id' => $equipment->id,
        ]);
    }

    /**
     * Test assigning overlapping crew blocks assignment.
     */
    public function test_overlapping_crew_assignment_fails(): void
    {
        // Booking 1: 10:00 to 12:00
        $booking1 = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Client One',
            'client_email' => 'one@test.com',
            'client_phone' => '0812121201',
            'booking_date' => '2026-07-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
            'total_price' => 500000.00,
            'paid_amount' => 0.00,
            'location' => 'Studio A',
        ]);

        // Booking 2: 11:00 to 13:00 (overlaps with booking 1)
        $booking2 = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Client Two',
            'client_email' => 'two@test.com',
            'client_phone' => '0812121202',
            'booking_date' => '2026-07-01',
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
            'status' => 'confirmed',
            'total_price' => 500000.00,
            'paid_amount' => 0.00,
            'location' => 'Studio B',
        ]);

        $crew = Crew::create([
            'name' => 'Shared Photographer',
            'role' => 'fotografer',
            'phone' => '0811111111',
            'is_active' => true,
        ]);

        // Assign crew to Booking 1 successfully
        $booking1->crews()->attach($crew->id);

        // Attempt to assign the same crew to Booking 2 (must fail due to overlap)
        $response = $this->actingAs($this->admin)->post("/admin/bookings/{$booking2->id}/assign", [
            'crew_ids' => [$crew->id],
            'equipment_ids' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('sudah dijadwalkan pada sesi foto lain di jam yang bentrok', session('error'));

        // Verify database relation does not exist for booking 2
        $this->assertDatabaseMissing('booking_crew', [
            'booking_id' => $booking2->id,
            'crew_id' => $crew->id,
        ]);
    }

    /**
     * Test assigning overlapping equipment blocks assignment.
     */
    public function test_overlapping_equipment_assignment_fails(): void
    {
        // Booking 1: 10:00 to 12:00
        $booking1 = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Client One',
            'client_email' => 'one@test.com',
            'client_phone' => '0812121201',
            'booking_date' => '2026-07-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
            'total_price' => 500000.00,
            'paid_amount' => 0.00,
            'location' => 'Studio A',
        ]);

        // Booking 2: 11:30 to 12:30 (overlaps with booking 1)
        $booking2 = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Client Two',
            'client_email' => 'two@test.com',
            'client_phone' => '0812121202',
            'booking_date' => '2026-07-01',
            'start_time' => '11:30:00',
            'end_time' => '12:30:00',
            'status' => 'confirmed',
            'total_price' => 500000.00,
            'paid_amount' => 0.00,
            'location' => 'Studio B',
        ]);

        $equipment = Equipment::create([
            'name' => 'Shared Sony A7 IV',
            'type' => 'kamera',
            'status' => 'active',
        ]);

        // Assign equipment to Booking 1 successfully
        $booking1->equipments()->attach($equipment->id);

        // Attempt to assign the same equipment to Booking 2 (must fail due to overlap)
        $response = $this->actingAs($this->admin)->post("/admin/bookings/{$booking2->id}/assign", [
            'crew_ids' => [],
            'equipment_ids' => [$equipment->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('sudah dialokasikan ke sesi foto lain di jam yang bentrok', session('error'));

        // Verify database relation does not exist for booking 2
        $this->assertDatabaseMissing('booking_equipment', [
            'booking_id' => $booking2->id,
            'equipment_id' => $equipment->id,
        ]);
    }
}
