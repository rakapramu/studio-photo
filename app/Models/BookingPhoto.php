<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'file_path',
        'watermarked_file_path',
        'is_selected',
        'edited_file_path',
        'status',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    protected $appends = [
        'raw_url',
        'edited_url',
    ];

    /**
     * Get the booking associated with the photo.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Public URL accessor for raw file.
     */
    public function getRawUrlAttribute(): string
    {
        $booking = $this->booking;
        $isPaid = $booking && (float) $booking->paid_amount >= (float) $booking->total_price;
        $disk = config('filesystems.gallery_disk', 'public');

        if ($isPaid) {
            return Storage::disk($disk)->url($this->file_path);
        }

        // Return watermarked file URL if unpaid, fallback to original raw if not watermarked
        return $this->watermarked_file_path 
            ? Storage::disk($disk)->url($this->watermarked_file_path)
            : Storage::disk($disk)->url($this->file_path);
    }

    /**
     * Public URL accessor for edited file.
     */
    public function getEditedUrlAttribute(): ?string
    {
        $disk = config('filesystems.gallery_disk', 'public');
        return $this->edited_file_path ? Storage::disk($disk)->url($this->edited_file_path) : null;
    }
}
