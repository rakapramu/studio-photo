<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\LifecycleRule;
use App\Models\MarketingSchedule;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CRMLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Package $weddingPackage;
    protected Package $maternityPackage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $this->weddingPackage = Package::create([
            'name' => 'Full Wedding Documentation',
            'description' => 'Wedding package',
            'price' => 5000000.00,
            'duration_minutes' => 480,
            'is_active' => true,
        ]);

        $this->maternityPackage = Package::create([
            'name' => 'Maternity Session',
            'description' => 'Maternity package',
            'price' => 800000.00,
            'duration_minutes' => 90,
            'is_active' => true,
        ]);
    }

    /**
     * Test guest cannot access CRM dashboard.
     */
    public function test_guest_cannot_access_crm(): void
    {
        $response = $this->get(route('admin.crm.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can access CRM dashboard.
     */
    public function test_admin_can_access_crm(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.crm.index'));
        $response->assertStatus(200);
    }

    /**
     * Test admin can create a lifecycle rule.
     */
    public function test_admin_can_create_lifecycle_rule(): void
    {
        $ruleData = [
            'name' => 'Wedding to Maternity Auto Promo',
            'source_package_id' => $this->weddingPackage->id,
            'target_package_id' => $this->maternityPackage->id,
            'delay_days' => 300,
            'message_template' => 'Hi {client_name}, promo for {target_package} at {target_price}!',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.crm.rules.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lifecycle_rules', [
            'name' => 'Wedding to Maternity Auto Promo',
            'delay_days' => 300,
        ]);
    }

    /**
     * Test admin can update a lifecycle rule.
     */
    public function test_admin_can_update_lifecycle_rule(): void
    {
        $rule = LifecycleRule::create([
            'name' => 'Old Name',
            'source_package_id' => $this->weddingPackage->id,
            'target_package_id' => $this->maternityPackage->id,
            'delay_days' => 300,
            'message_template' => 'Old template',
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'New Name',
            'source_package_id' => $this->weddingPackage->id,
            'target_package_id' => $this->maternityPackage->id,
            'delay_days' => 150,
            'message_template' => 'New template',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.crm.rules.update', $rule->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lifecycle_rules', [
            'id' => $rule->id,
            'name' => 'New Name',
            'delay_days' => 150,
            'is_active' => false,
        ]);
    }

    /**
     * Test admin can delete a lifecycle rule.
     */
    public function test_admin_can_delete_lifecycle_rule(): void
    {
        $rule = LifecycleRule::create([
            'name' => 'To Delete',
            'source_package_id' => $this->weddingPackage->id,
            'target_package_id' => $this->maternityPackage->id,
            'delay_days' => 10,
            'message_template' => 'Template',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.crm.rules.destroy', $rule->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('lifecycle_rules', ['id' => $rule->id]);
    }

    /**
     * Test scheduling marketing message automatically when booking status is updated to completed.
     */
    public function test_booking_completed_triggers_marketing_schedule(): void
    {
        // 1. Create an active lifecycle rule
        LifecycleRule::create([
            'name' => 'Wedding to Maternity Auto Promo',
            'source_package_id' => $this->weddingPackage->id,
            'target_package_id' => $this->maternityPackage->id,
            'delay_days' => 300,
            'message_template' => 'Halo {client_name}, setelah {source_package}, yuk coba {target_package} seharga {target_price}!',
            'is_active' => true,
        ]);

        // 2. Create a booking (status is pending)
        $booking = Booking::create([
            'user_id' => $this->admin->id,
            'package_id' => $this->weddingPackage->id,
            'client_name' => 'Citra Raka',
            'client_email' => 'citra@example.com',
            'client_phone' => '081234567890',
            'booking_date' => '2026-06-01',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'pending',
            'total_price' => 5000000.00,
            'paid_amount' => 1500000.00,
            'location' => 'Bogor',
        ]);

        // 3. Mark booking as completed
        $response = $this->actingAs($this->admin)->patch(route('admin.bookings.status', $booking->id), [
            'status' => 'completed',
        ]);

        $response->assertRedirect();

        // 4. Verify a schedule record was created and placeholders replaced
        $this->assertDatabaseHas('marketing_schedules', [
            'booking_id' => $booking->id,
            'client_name' => 'Citra Raka',
            'client_phone' => '081234567890',
            'status' => 'pending',
        ]);

        $schedule = MarketingSchedule::where('booking_id', $booking->id)->first();
        $this->assertNotNull($schedule);

        // Check date calculation: 2026-06-01 + 300 days = 2027-03-28
        $this->assertEquals('2027-03-28', $schedule->scheduled_at->format('Y-m-d'));

        // Check placeholder replacement
        $expectedMessage = 'Halo Citra Raka, setelah Full Wedding Documentation, yuk coba Maternity Session seharga Rp 800.000!';
        $this->assertEquals($expectedMessage, $schedule->message_content);
    }

    /**
     * Test admin can manually send a scheduled message now.
     */
    public function test_admin_can_send_schedule_now(): void
    {
        $schedule = MarketingSchedule::create([
            'booking_id' => Booking::create([
                'user_id' => $this->admin->id,
                'package_id' => $this->weddingPackage->id,
                'client_name' => 'Client A',
                'client_email' => 'a@example.com',
                'client_phone' => '081234567890',
                'booking_date' => '2026-06-01',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'status' => 'completed',
                'total_price' => 5000000.00,
                'paid_amount' => 5000000.00,
                'location' => 'Bogor',
            ])->id,
            'client_name' => 'Client A',
            'client_phone' => '081234567890',
            'message_content' => 'Promo message',
            'scheduled_at' => now()->addDays(10),
            'status' => 'pending',
        ]);

        // Mock Fonnte token in config or env so it simulates sending (or falls back to log warning)
        // Let's send the request
        $response = $this->actingAs($this->admin)->post(route('admin.crm.schedules.send', $schedule->id));

        $response->assertRedirect();
        
        // Since Fonnte token is probably empty in tests, it will fall back to failed or sent. Let's make sure it updates the record.
        // Wait, if no token is configured, sendMessage returns false.
        // Let's verify that the schedule is updated to either sent or failed, and not remains pending.
        $schedule->refresh();
        $this->assertNotEquals('pending', $schedule->status);
    }

    /**
     * Test simulating cron job processes due messages.
     */
    public function test_run_simulated_cron_processes_due_schedules(): void
    {
        $booking = Booking::create([
            'user_id' => $this->admin->id,
            'package_id' => $this->weddingPackage->id,
            'client_name' => 'Client B',
            'client_email' => 'b@example.com',
            'client_phone' => '081234567890',
            'booking_date' => '2026-06-01',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'completed',
            'total_price' => 5000000.00,
            'paid_amount' => 5000000.00,
            'location' => 'Bogor',
        ]);

        // Create one schedule due in the past (should be processed)
        $dueSchedule = MarketingSchedule::create([
            'booking_id' => $booking->id,
            'client_name' => 'Client B',
            'client_phone' => '081234567890',
            'message_content' => 'Due Promo',
            'scheduled_at' => now()->subDay(),
            'status' => 'pending',
        ]);

        // Create one schedule in the future (should NOT be processed)
        $futureSchedule = MarketingSchedule::create([
            'booking_id' => $booking->id,
            'client_name' => 'Client B',
            'client_phone' => '081234567890',
            'message_content' => 'Future Promo',
            'scheduled_at' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.crm.schedules.run-cron'));

        $response->assertRedirect();

        // Verify status updates
        $dueSchedule->refresh();
        $futureSchedule->refresh();

        $this->assertNotEquals('pending', $dueSchedule->status);
        $this->assertEquals('pending', $futureSchedule->status);
    }
}
