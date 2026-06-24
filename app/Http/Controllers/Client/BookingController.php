<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Show the public booking form.
     */
    public function index(): Response
    {
        $packages = Package::where('is_active', true)->get();

        return Inertia::render('Client/Booking', [
            'packages' => $packages,
        ]);
    }

    /**
     * Store a new booking.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'string', 'email', 'max:255'],
            'client_phone' => ['required', 'string', 'max:20'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $package = Package::findOrFail($request->package_id);
        
        // Hitung jam selesai berdasarkan durasi paket secara aman di server
        $startTime = Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = $startTime->copy()->addMinutes($package->duration_minutes);

        $formattedStartTime = $startTime->format('H:i:s');
        $formattedEndTime = $endTime->format('H:i:s');

        // Cek bentrok jadwal (overlap check) untuk booking terkonfirmasi/pending
        $overlap = Booking::where('booking_date', $request->booking_date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($formattedStartTime, $formattedEndTime) {
                $query->where('start_time', '<', $formattedEndTime)
                      ->where('end_time', '>', $formattedStartTime);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_time' => 'Slot waktu pada tanggal tersebut sudah dipesan. Silakan pilih jam atau tanggal lain.',
            ]);
        }

        // Simpan booking
        Booking::create([
            'user_id' => auth()->id(), // jika klien login
            'package_id' => $package->id,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'client_phone' => $request->client_phone,
            'booking_date' => $request->booking_date,
            'start_time' => $formattedStartTime,
            'end_time' => $formattedEndTime,
            'status' => 'pending',
            'total_price' => $package->price,
            'paid_amount' => 0.00,
            'location' => $request->location,
            'notes' => $request->notes,
        ]);

        return redirect()->route('booking.success');
    }

    /**
     * Show booking success page.
     */
    public function success(): Response
    {
        return Inertia::render('Client/BookingSuccess');
    }
}
