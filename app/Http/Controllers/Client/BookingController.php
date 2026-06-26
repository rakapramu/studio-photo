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
            'studioConfig' => [
                'latitude' => (float) \App\Models\Setting::getValue('studio_latitude', config('services.studio.latitude')),
                'longitude' => (float) \App\Models\Setting::getValue('studio_longitude', config('services.studio.longitude')),
                'fuel_cost_per_km' => (float) \App\Models\Setting::getValue('fuel_cost_per_km', config('services.studio.fuel_cost_per_km')),
                'accommodation_per_night' => (float) \App\Models\Setting::getValue('accommodation_cost_per_night', config('services.studio.accommodation_per_night')),
                'studio_name' => \App\Models\Setting::getValue('studio_name', 'Studio Photo Raka'),
            ]
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
            
            // Travel / Outdoor fields
            'is_outdoor' => ['required', 'boolean'],
            'travel_distance' => ['required_if:is_outdoor,true', 'numeric', 'min:0'],
            'fuel_cost' => ['required_if:is_outdoor,true', 'numeric', 'min:0'],
            'toll_cost' => ['required_if:is_outdoor,true', 'numeric', 'min:0'],
            'accommodation_cost' => ['required_if:is_outdoor,true', 'numeric', 'min:0'],
            'travel_surcharge' => ['required_if:is_outdoor,true', 'numeric', 'min:0'],
            'is_overnight' => ['required_if:is_outdoor,true', 'boolean'],
            'location_latitude' => ['nullable', 'numeric'],
            'location_longitude' => ['nullable', 'numeric'],
        ]);

        $package = Package::findOrFail($request->package_id);
        
        // Recalculate costs on the server to prevent client manipulation
        $isOutdoor = (bool) $request->input('is_outdoor', false);
        $travelDistance = 0.00;
        $fuelCost = 0.00;
        $tollCost = 0.00;
        $accommodationCost = 0.00;
        $travelSurcharge = 0.00;
        $isOvernight = false;
        $lat = null;
        $lng = null;

        if ($isOutdoor) {
            $travelDistance = (float) $request->input('travel_distance', 0);
            $fuelCostPerKm = (float) \App\Models\Setting::getValue('fuel_cost_per_km', config('services.studio.fuel_cost_per_km'));
            $fuelCost = $travelDistance * 2 * $fuelCostPerKm; // PP (Round trip)
            $tollCost = (float) $request->input('toll_cost', 0);
            
            $isOvernight = (bool) $request->input('is_overnight', false);
            $accommodationRate = (float) \App\Models\Setting::getValue('accommodation_cost_per_night', config('services.studio.accommodation_per_night'));
            $accommodationCost = $isOvernight ? $accommodationRate : 0.00;
            
            $travelSurcharge = $fuelCost + $tollCost + $accommodationCost;
            $lat = $request->input('location_latitude');
            $lng = $request->input('location_longitude');
        }

        $totalPrice = (float) $package->price + $travelSurcharge;

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
            'total_price' => $totalPrice,
            'paid_amount' => 0.00,
            'location' => $request->location,
            'notes' => $request->notes,
            'is_outdoor' => $isOutdoor,
            'travel_distance' => $travelDistance,
            'fuel_cost' => $fuelCost,
            'toll_cost' => $tollCost,
            'accommodation_cost' => $accommodationCost,
            'travel_surcharge' => $travelSurcharge,
            'is_overnight' => $isOvernight,
            'location_latitude' => $lat,
            'location_longitude' => $lng,
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
