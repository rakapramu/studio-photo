<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingGalleryController extends Controller
{
    /**
     * Display the secure client photo gallery portal.
     */
    public function show(Booking $booking, string $hash): Response
    {
        $expectedHash = md5($booking->id . $booking->client_email . config('app.key'));
        if ($hash !== $expectedHash) {
            abort(403, 'Akses Galeri Tidak Sah atau Tautan Kedaluwarsa.');
        }

        $booking->load(['package', 'photos' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return Inertia::render('Client/Gallery', [
            'booking' => $booking,
            'hash' => $hash,
        ]);
    }

    /**
     * Store the client photo selection for proofing.
     */
    public function selectPhotos(Request $request, Booking $booking, string $hash): RedirectResponse
    {
        $expectedHash = md5($booking->id . $booking->client_email . config('app.key'));
        if ($hash !== $expectedHash) {
            abort(403, 'Tindakan tidak sah.');
        }

        $validated = $request->validate([
            'photo_ids' => ['nullable', 'array'],
            'photo_ids.*' => ['exists:booking_photos,id'],
        ]);

        $photoIds = $validated['photo_ids'] ?? [];

        // Verify that all selected photo IDs belong to this booking
        $validPhotosCount = BookingPhoto::where('booking_id', $booking->id)
            ->whereIn('id', $photoIds)
            ->count();

        if (count($photoIds) > 0 && $validPhotosCount !== count($photoIds)) {
            return redirect()->back()->with('error', 'Gagal menyimpan pilihan: Beberapa foto tidak valid.');
        }

        try {
            DB::transaction(function () use ($booking, $photoIds) {
                // Reset all photos for this booking
                BookingPhoto::where('booking_id', $booking->id)->update([
                    'is_selected' => false,
                    'status' => 'raw'
                ]);

                // Update selected photos
                if (count($photoIds) > 0) {
                    BookingPhoto::whereIn('id', $photoIds)->update([
                        'is_selected' => true,
                        'status' => 'selected'
                    ]);
                }
            });

            return redirect()->back()->with('success', 'Pilihan foto berhasil disimpan. Tim editor kami akan segera memproses edit pascaproduksi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan pilihan: ' . $e->getMessage());
        }
    }
}
