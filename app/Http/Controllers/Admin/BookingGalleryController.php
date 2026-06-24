<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\WatermarkService;

class BookingGalleryController extends Controller
{
    /**
     * Display the gallery management page for a booking.
     */
    public function index(Booking $booking): Response
    {
        $booking->load(['package', 'photos']);

        $hash = md5($booking->id . $booking->client_email . config('app.key'));
        $clientGalleryUrl = url("/booking/{$booking->id}/gallery/{$hash}");

        return Inertia::render('Admin/Bookings/Gallery', [
            'booking' => $booking,
            'clientGalleryUrl' => $clientGalleryUrl,
        ]);
    }

    /**
     * Upload photos (multiple raw/preview files) for a booking.
     */
    public function upload(Request $request, Booking $booking, WatermarkService $watermarkService): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['required', 'image', 'max:12288'], // max 12MB per photo
        ]);

        $disk = config('filesystems.gallery_disk', 'public');

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                // Store photo in galleries/{booking_id}/raw on dynamic disk
                $path = $file->store("galleries/{$booking->id}/raw", $disk);
                
                // Define watermark path
                $filename = basename($path);
                $watermarkPath = "galleries/{$booking->id}/watermarked/{$filename}";

                // Generate watermarked copy
                $watermarkService->applyWatermark($path, $watermarkPath);

                BookingPhoto::create([
                    'booking_id' => $booking->id,
                    'file_path' => $path,
                    'watermarked_file_path' => $watermarkPath,
                    'is_selected' => false,
                    'status' => 'raw',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Foto mentah berhasil diunggah ke galeri.');
    }

    /**
     * Delete a photo from the gallery.
     */
    public function destroy(BookingPhoto $photo): RedirectResponse
    {
        $disk = config('filesystems.gallery_disk', 'public');

        // Delete raw file from dynamic disk
        if (Storage::disk($disk)->exists($photo->file_path)) {
            Storage::disk($disk)->delete($photo->file_path);
        }

        // Delete watermarked file if exists from dynamic disk
        if ($photo->watermarked_file_path && Storage::disk($disk)->exists($photo->watermarked_file_path)) {
            Storage::disk($disk)->delete($photo->watermarked_file_path);
        }

        // Delete edited file if exists from dynamic disk
        if ($photo->edited_file_path && Storage::disk($disk)->exists($photo->edited_file_path)) {
            Storage::disk($disk)->delete($photo->edited_file_path);
        }

        $photo->delete();

        return redirect()->back()->with('success', 'Foto berhasil dihapus dari galeri.');
    }

    /**
     * Upload an edited version of a photo.
     */
    public function uploadEdited(Request $request, BookingPhoto $photo): RedirectResponse
    {
        $request->validate([
            'edited_photo' => ['required', 'image', 'max:12288'],
        ]);

        $disk = config('filesystems.gallery_disk', 'public');

        if ($request->hasFile('edited_photo')) {
            // Delete old edited file if exists
            if ($photo->edited_file_path && Storage::disk($disk)->exists($photo->edited_file_path)) {
                Storage::disk($disk)->delete($photo->edited_file_path);
            }

            // Store in galleries/{booking_id}/edited on dynamic disk
            $file = $request->file('edited_photo');
            $path = $file->store("galleries/{$photo->booking_id}/edited", $disk);

            $photo->update([
                'edited_file_path' => $path,
                'status' => 'edited',
            ]);
        }

        return redirect()->back()->with('success', 'Foto hasil edit pascaproduksi berhasil diunggah.');
    }
}
