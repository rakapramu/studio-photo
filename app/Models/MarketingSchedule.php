<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'lifecycle_rule_id',
        'client_name',
        'client_phone',
        'message_content',
        'scheduled_at',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the booking associated with this marketing schedule.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the lifecycle rule associated with this marketing schedule.
     */
    public function lifecycleRule(): BelongsTo
    {
        return $this->belongsTo(LifecycleRule::class);
    }
}
