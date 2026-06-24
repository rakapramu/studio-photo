<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Contract;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Package $package;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create();

        // Create a package
        $this->package = Package::create([
            'name' => 'Gold Wedding Session',
            'description' => 'Gold package description',
            'price' => 5000000.00,
            'duration_minutes' => 300,
            'is_active' => true,
        ]);

        // Create a booking
        $this->booking = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Alice Margatroid',
            'client_email' => 'alice@marisa.com',
            'client_phone' => '081234567890',
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 5000000.00,
            'paid_amount' => 1500000.00,
            'location' => 'Forest of Magic',
        ]);
    }

    /**
     * Test admin payments page authentication restriction.
     */
    public function test_admin_payments_page_requires_auth(): void
    {
        $response = $this->get('/admin/payments');
        $response->assertRedirect('/login');
    }

    /**
     * Test admin payments page loaded with correct data and stats.
     */
    public function test_admin_can_view_payments_and_stats(): void
    {
        // Create some payments
        Payment::create([
            'booking_id' => $this->booking->id,
            'amount' => 1500000.00,
            'status' => 'paid',
            'type' => 'down_payment',
            'snap_token' => 'snap-token-111',
            'payment_method' => 'gopay',
            'transaction_id' => 'tx-111',
        ]);

        Payment::create([
            'booking_id' => $this->booking->id,
            'amount' => 3500000.00,
            'status' => 'pending',
            'type' => 'final_payment',
            'snap_token' => 'snap-token-222',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'tx-222',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/payments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Payments/Index')
            ->has('payments', 2)
            ->has('stats')
            ->where('stats.total_revenue', 1500000)
            ->where('stats.pending_count', 1)
            ->where('stats.success_count', 1)
            ->where('stats.failed_count', 0)
        );
    }

    /**
     * Test admin contracts page authentication restriction.
     */
    public function test_admin_contracts_page_requires_auth(): void
    {
        $response = $this->get('/admin/contracts');
        $response->assertRedirect('/login');
    }

    /**
     * Test admin contracts page loaded with correct data and stats.
     */
    public function test_admin_can_view_contracts_and_stats(): void
    {
        // Create signed contract
        Contract::create([
            'booking_id' => $this->booking->id,
            'contract_text' => 'This is a sample contract text for Alice.',
            'signature_path' => 'signatures/alice-sign.png',
            'signed_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);

        // Create another booking for draft contract
        $booking2 = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Reimu Hakurei',
            'client_email' => 'reimu@hakurei.com',
            'client_phone' => '082233445566',
            'booking_date' => '2026-08-21',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 5000000.00,
            'paid_amount' => 0.00,
            'location' => 'Hakurei Shrine',
        ]);

        Contract::create([
            'booking_id' => $booking2->id,
            'contract_text' => 'This is a sample contract text for Reimu.',
            'signature_path' => null,
            'signed_at' => null,
            'ip_address' => null,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/contracts');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Contracts/Index')
            ->has('contracts', 2)
            ->has('stats')
            ->where('stats.total_count', 2)
            ->where('stats.signed_count', 1)
            ->where('stats.draft_count', 1)
        );
    }
}
