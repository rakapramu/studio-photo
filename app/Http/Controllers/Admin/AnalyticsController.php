<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Crew;
use App\Models\Expense;
use App\Models\Package;
use App\Models\Payment;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Display the financial analytics dashboard.
     */
    public function index(): Response
    {
        // 1. Calculate General Summary Metrics
        $grossRevenue = (float) Payment::where('status', 'paid')->sum('amount');
        
        $operationalExpenses = (float) Expense::sum('amount');
        
        // Sum crew commission costs for active bookings
        $allActiveBookings = Booking::with('crews')
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();
            
        $totalCrewCommission = 0.0;
        foreach ($allActiveBookings as $booking) {
            foreach ($booking->crews as $crew) {
                $totalCrewCommission += (float) $crew->fee_per_session;
            }
        }
        
        $totalExpenses = $operationalExpenses + $totalCrewCommission;
        $netProfit = $grossRevenue - $totalExpenses;
        
        // Sum outstanding client balances (receivables) for active bookings
        $activeBookings = Booking::with('package')->whereIn('status', ['pending', 'confirmed'])->get();
        $totalReceivables = 0.0;
        $receivablesDetails = [];
        foreach ($activeBookings as $ab) {
            $due = (float) $ab->total_price - (float) $ab->paid_amount;
            if ($due > 0) {
                $totalReceivables += $due;
                $receivablesDetails[] = [
                    'id' => $ab->id,
                    'client_name' => $ab->client_name,
                    'client_phone' => $ab->client_phone,
                    'client_email' => $ab->client_email,
                    'package_name' => $ab->package?->name ?? 'Custom',
                    'booking_date' => $ab->booking_date ? $ab->booking_date->format('Y-m-d') : '-',
                    'total_price' => (float) $ab->total_price,
                    'paid_amount' => (float) $ab->paid_amount,
                    'remaining_balance' => $due,
                    'status' => $ab->status,
                ];
            }
        }

        // 2. Generate Monthly Revenue vs Expense Data (Last 6 Months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->translatedFormat('F Y');
            
            // Monthly revenue
            $rev = (float) Payment::where('status', 'paid')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
                
            // Monthly operational expenses
            $opsExp = (float) Expense::whereYear('expense_date', $month->year)
                ->whereMonth('expense_date', $month->month)
                ->sum('amount');
                
            // Monthly crew commissions
            $monthlyBookings = Booking::with('crews')
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereYear('booking_date', $month->year)
                ->whereMonth('booking_date', $month->month)
                ->get();
                
            $crewExp = 0.0;
            foreach ($monthlyBookings as $mb) {
                foreach ($mb->crews as $mc) {
                    $crewExp += (float) $mc->fee_per_session;
                }
            }
            
            $monthExp = $opsExp + $crewExp;
            
            $monthlyTrends[] = [
                'label' => $monthName,
                'revenue' => $rev,
                'expenses' => $monthExp,
                'profit' => $rev - $monthExp,
            ];
        }

        // 3. Package Popularity & Profitability
        $packagePerformance = Package::all()->map(function ($pkg) {
            $bookingCount = Booking::where('package_id', $pkg->id)->count();
            
            $revenue = (float) Payment::where('status', 'paid')
                ->whereHas('booking', function ($q) use ($pkg) {
                    $q->where('package_id', $pkg->id);
                })->sum('amount');
                
            return [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'bookings_count' => $bookingCount,
                'revenue' => $revenue,
            ];
        })->sortByDesc('bookings_count')->values()->all();

        // 4. Profitability per Project Booking
        $projectProfitability = Booking::with(['package', 'crews'])
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($b) {
                $crewCost = 0.0;
                foreach ($b->crews as $crew) {
                    $crewCost += (float) $crew->fee_per_session;
                }
                
                $revenue = (float) $b->paid_amount;
                
                return [
                    'id' => $b->id,
                    'client_name' => $b->client_name,
                    'package_name' => $b->package?->name ?? 'Custom',
                    'booking_date' => $b->booking_date ? $b->booking_date->format('Y-m-d') : '-',
                    'status' => $b->status,
                    'total_price' => (float) $b->total_price,
                    'paid_amount' => $revenue,
                    'crew_cost' => $crewCost,
                    'net_profit' => $revenue - $crewCost,
                ];
            })->all();

        // 5. Crew Commission Ledger
        $crewLedger = Crew::all()->map(function ($c) {
            $sessionsCount = $c->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
                
            $earnings = $sessionsCount * (float) $c->fee_per_session;
            
            return [
                'id' => $c->id,
                'name' => $c->name,
                'role' => $c->role,
                'fee_per_session' => (float) $c->fee_per_session,
                'sessions_count' => $sessionsCount,
                'total_earnings' => $earnings,
            ];
        })->sortByDesc('total_earnings')->values()->all();

        return Inertia::render('Admin/Analytics/Index', [
            'stats' => [
                'gross_revenue' => $grossRevenue,
                'operational_expenses' => $operationalExpenses,
                'crew_commissions' => $totalCrewCommission,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'total_receivables' => $totalReceivables,
                'receivables_details' => $receivablesDetails,
            ],
            'monthlyTrends' => $monthlyTrends,
            'packagePerformance' => $packagePerformance,
            'projectProfitability' => $projectProfitability,
            'crewLedger' => $crewLedger,
        ]);
    }

    /**
     * Display a print-ready financial and audit report.
     */
    public function report(\Illuminate\Http\Request $request): Response
    {
        $filterType = $request->input('filter_type', 'all');
        
        $startDate = null;
        $endDate = null;
        $periodLabel = '';

        if ($filterType === 'date_range') {
            $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')))->endOfDay();
            $periodLabel = $startDate->translatedFormat('d F Y') . ' - ' . $endDate->translatedFormat('d F Y');
        } elseif ($filterType === 'month') {
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);
            $carbonDate = Carbon::createFromDate($year, $month, 1);
            $startDate = $carbonDate->copy()->startOfMonth()->startOfDay();
            $endDate = $carbonDate->copy()->endOfMonth()->endOfDay();
            $periodLabel = $carbonDate->translatedFormat('F Y');
        } elseif ($filterType === 'year') {
            $year = (int) $request->input('year', Carbon::now()->year);
            $carbonDate = Carbon::createFromDate($year, 1, 1);
            $startDate = $carbonDate->copy()->startOfYear()->startOfDay();
            $endDate = $carbonDate->copy()->endOfYear()->endOfDay();
            $periodLabel = 'Tahun ' . $year;
        } else {
            $startDate = Carbon::parse('2020-01-01')->startOfDay();
            $endDate = Carbon::now()->addYears(5)->endOfDay();
            $periodLabel = 'Semua Periode';
        }

        // 1. Fetch Revenue Payments
        $payments = Payment::with(['booking.package'])
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->created_at->format('Y-m-d'),
                    'invoice' => 'PAY-' . $p->id,
                    'client_name' => $p->booking?->client_name ?? 'Client Umum',
                    'description' => 'Pembayaran ' . ($p->type === 'down_payment' ? 'DP' : 'Pelunasan') . ' - ' . ($p->booking?->package?->name ?? 'Custom'),
                    'amount' => (float) $p->amount,
                    'payment_method' => strtoupper($p->payment_method ?? 'Midtrans'),
                ];
            });
            
        $totalRevenue = $payments->sum('amount');

        // 2. Fetch Operational Expenses
        $operationalExpensesList = Expense::whereBetween('expense_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->map(function ($e) {
                return [
                    'date' => $e->expense_date->format('Y-m-d'),
                    'title' => $e->title,
                    'description' => $e->notes ?? '-',
                    'amount' => (float) $e->amount,
                    'category' => 'Operasional',
                ];
            });
            
        $totalOperationalExpenses = $operationalExpensesList->sum('amount');

        // 3. Fetch Crew Commission Expenses
        $bookingsWithCrews = Booking::with(['crews', 'package'])
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $crewCommissionsList = collect();
        foreach ($bookingsWithCrews as $booking) {
            foreach ($booking->crews as $crew) {
                $crewCommissionsList->push([
                    'date' => $booking->booking_date->format('Y-m-d'),
                    'title' => 'Komisi Kru - ' . $crew->name,
                    'description' => 'Sesi ' . $booking->client_name . ' (' . ($booking->package?->name ?? 'Custom') . ')',
                    'amount' => (float) $crew->fee_per_session,
                    'category' => 'Komisi Kru',
                ]);
            }
        }
        
        $totalCrewCommissions = $crewCommissionsList->sum('amount');
        
        // Combine and sort expenses
        $allExpenses = $operationalExpensesList->concat($crewCommissionsList)->sortBy('date')->values()->all();
        $totalExpenses = $totalOperationalExpenses + $totalCrewCommissions;
        $netProfit = $totalRevenue - $totalExpenses;

        return Inertia::render('Admin/Analytics/Report', [
            'filters' => [
                'filter_type' => $filterType,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'month' => $request->input('month'),
                'year' => $request->input('year'),
            ],
            'periodLabel' => $periodLabel,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_operational_expenses' => $totalOperationalExpenses,
                'total_crew_commissions' => $totalCrewCommissions,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
            ],
            'revenueList' => $payments->all(),
            'expensesList' => $allExpenses,
            'generatedAt' => Carbon::now()->translatedFormat('d F Y H:i:s'),
            'adminName' => auth()->user()?->name ?? 'Administrator',
        ]);
    }
}
