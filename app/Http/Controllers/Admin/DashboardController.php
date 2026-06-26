<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $stats = [
            'totalBookings' => Booking::count(),
            'totalPackages' => Package::count(),
            'pendingBookings' => Booking::where('status', 'pending')->count(),
            'confirmedBookings' => Booking::where('status', 'confirmed')->count(),
        ];

        $recentBookings = Booking::with(['package', 'crews', 'equipments'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $allBookings = Booking::with(['package', 'crews', 'equipments'])
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'allBookings' => $allBookings,
        ]);
    }
}
