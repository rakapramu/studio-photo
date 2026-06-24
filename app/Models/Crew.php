<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Crew extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'phone',
        'email',
        'is_active',
        'fee_per_session',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_per_session' => 'float',
    ];

    /**
     * Get the bookings associated with the crew.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_crew');
    }
}
