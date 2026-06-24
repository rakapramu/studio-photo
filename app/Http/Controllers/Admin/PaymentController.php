<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payments in the admin dashboard.
     */
    public function index(): Response
    {
        $payments = Payment::with(['booking.package'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate simple dashboard stats
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $successfulPayments = Payment::where('status', 'paid')->count();
        $failedPayments = Payment::whereIn('status', ['failed', 'expired'])->count();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'stats' => [
                'total_revenue' => (float) $totalRevenue,
                'pending_count' => $pendingPayments,
                'success_count' => $successfulPayments,
                'failed_count' => $failedPayments,
            ],
        ]);
    }
}
