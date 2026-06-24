<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Crew;
use App\Models\Expense;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Package $package;
    private Booking $booking;
    private Crew $crew;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $this->package = Package::create([
            'name' => 'Signature Wedding Session',
            'description' => 'Wedding package description',
            'price' => 3000000.00,
            'duration_minutes' => 240,
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Alice Margatroid',
            'client_email' => 'alice@marisa.com',
            'client_phone' => '081234567890',
            'booking_date' => '2026-06-20',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
            'status' => 'confirmed',
            'total_price' => 3000000.00,
            'paid_amount' => 900000.00, // DP paid
            'location' => 'Forest of Magic',
        ]);

        $this->crew = Crew::create([
            'name' => 'Marisa Kirisame',
            'role' => 'fotografer',
            'phone' => '081122334455',
            'is_active' => true,
            'fee_per_session' => 200000.00,
        ]);

        $this->booking->crews()->attach($this->crew->id);
    }

    /**
     * Test analytics & expense page authentication restrictions.
     */
    public function test_analytics_and_expense_pages_require_auth(): void
    {
        $this->get('/admin/analytics')->assertRedirect('/login');
        $this->get('/admin/expenses')->assertRedirect('/login');
    }

    /**
     * Test expenses CRUD operations.
     */
    public function test_expenses_crud_operations(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/expenses');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Expenses/Index')
            ->has('expenses')
            ->has('totalAmount')
            ->has('thisMonthAmount')
            ->has('thisMonthLabel')
        );

        // 2. Store
        $response = $this->actingAs($this->admin)->post('/admin/expenses', [
            'title' => 'Sewa Softbox Studio',
            'amount' => 150000.00,
            'expense_date' => '2026-06-22',
            'notes' => 'Beli di online shop',
        ]);
        $response->assertRedirect('/admin/expenses');
        $this->assertDatabaseHas('expenses', [
            'title' => 'Sewa Softbox Studio',
            'amount' => 150000.00,
        ]);

        $expense = Expense::where('title', 'Sewa Softbox Studio')->first();

        // 3. Update
        $response = $this->actingAs($this->admin)->put("/admin/expenses/{$expense->id}", [
            'title' => 'Sewa Softbox Studio Updated',
            'amount' => 180000.00,
            'expense_date' => '2026-06-22',
            'notes' => 'Harga naik sedikit',
        ]);
        $response->assertRedirect('/admin/expenses');
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Sewa Softbox Studio Updated',
            'amount' => 180000.00,
        ]);

        // 4. Destroy
        $response = $this->actingAs($this->admin)->delete("/admin/expenses/{$expense->id}");
        $response->assertRedirect('/admin/expenses');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    /**
     * Test financial analytics page calculations.
     */
    public function test_analytics_financial_calculations(): void
    {
        // Add paid payment
        Payment::create([
            'booking_id' => $this->booking->id,
            'amount' => 900000.00,
            'status' => 'paid',
            'type' => 'down_payment',
            'snap_token' => 'snap-900',
            'transaction_id' => 'tx-900',
            'payment_method' => 'gopay',
        ]);

        // Add operational expense
        Expense::create([
            'title' => 'Listrik Studio',
            'amount' => 350000.00,
            'expense_date' => '2026-06-24',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/analytics');
        $response->assertStatus(200);

        // Expectations:
        // gross_revenue: 900000.00
        // operational_expenses: 350000.00
        // crew_commissions: 200000.00 (Marisa Kirisame is assigned to Alice's active booking)
        // total_expenses: 350000 + 200000 = 550000.00
        // net_profit: 900000 - 550000 = 350000.00
        // total_receivables: 3000000 - 900000 = 2100000.00

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->has('stats')
            ->where('stats.gross_revenue', 900000)
            ->where('stats.operational_expenses', 350000)
            ->where('stats.crew_commissions', 200000)
            ->where('stats.total_expenses', 550000)
            ->where('stats.net_profit', 350000)
            ->where('stats.total_receivables', 2100000)
            ->has('stats.receivables_details')
            ->where('stats.receivables_details.0.client_name', 'Alice Margatroid')
            ->where('stats.receivables_details.0.remaining_balance', 2100000)
            ->has('monthlyTrends')
            ->has('packagePerformance')
            ->has('projectProfitability')
            ->has('crewLedger')
        );

        // Assert crew ledger contents
        $response->assertInertia(fn ($page) => $page
            ->where('crewLedger.0.name', 'Marisa Kirisame')
            ->where('crewLedger.0.sessions_count', 1)
            ->where('crewLedger.0.total_earnings', 200000)
        );
    }

    /**
     * Test generating and filtering financial report.
     */
    public function test_generating_financial_report(): void
    {
        // 1. Unauthenticated redirect
        $this->get('/admin/analytics/report')->assertRedirect('/login');

        // 2. Add paid payment and expense
        Payment::create([
            'booking_id' => $this->booking->id,
            'amount' => 900000.00,
            'status' => 'paid',
            'type' => 'down_payment',
            'snap_token' => 'snap-900',
            'transaction_id' => 'tx-900',
            'payment_method' => 'gopay',
            'created_at' => '2026-06-20 12:00:00',
        ]);

        Expense::create([
            'title' => 'Background Paper Roll',
            'amount' => 450000.00,
            'expense_date' => '2026-06-22',
        ]);

        // 3. Get monthly filter report
        $response = $this->actingAs($this->admin)->get('/admin/analytics/report?filter_type=month&month=6&year=2026');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Report')
            ->has('filters')
            ->where('filters.filter_type', 'month')
            ->where('filters.month', '6')
            ->where('filters.year', '2026')
            ->where('periodLabel', 'Juni 2026')
            ->has('summary')
            ->where('summary.total_revenue', 900000)
            ->where('summary.total_operational_expenses', 450000)
            ->where('summary.total_crew_commissions', 200000) // Crew Marisa
            ->where('summary.total_expenses', 650000)
            ->where('summary.net_profit', 250000)
            ->has('revenueList')
            ->where('revenueList.0.amount', 900000)
            ->has('expensesList')
            ->where('expensesList.0.title', 'Komisi Kru - Marisa Kirisame')
            ->where('expensesList.1.title', 'Background Paper Roll')
            ->has('generatedAt')
            ->has('adminName')
        );
    }
}
