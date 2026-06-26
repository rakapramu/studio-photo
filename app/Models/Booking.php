<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'client_name',
        'client_email',
        'client_phone',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'total_price',
        'paid_amount',
        'location',
        'notes',
        'is_outdoor',
        'travel_distance',
        'fuel_cost',
        'toll_cost',
        'accommodation_cost',
        'travel_surcharge',
        'is_overnight',
        'location_latitude',
        'location_longitude',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_outdoor' => 'boolean',
        'travel_distance' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'accommodation_cost' => 'decimal:2',
        'travel_surcharge' => 'decimal:2',
        'is_overnight' => 'boolean',
        'location_latitude' => 'decimal:7',
        'location_longitude' => 'decimal:7',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package associated with the booking.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the payments for the booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the contract associated with the booking.
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Get the equipments assigned to the booking.
     */
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'booking_equipment');
    }

    /**
     * Get the crews assigned to the booking.
     */
    public function crews(): BelongsToMany
    {
        return $this->belongsToMany(Crew::class, 'booking_crew');
    }

    /**
     * Get the photos associated with the booking.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(BookingPhoto::class);
    }

    /**
     * Get the marketing schedules for this booking.
     */
    public function marketingSchedules(): HasMany
    {
        return $this->hasMany(MarketingSchedule::class);
    }
}
