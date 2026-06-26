<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'price',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the bookings for the package.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the lifecycle rules where this package is the source.
     */
    public function sourceRules(): HasMany
    {
        return $this->hasMany(LifecycleRule::class, 'source_package_id');
    }

    /**
     * Get the lifecycle rules where this package is the target.
     */
    public function targetRules(): HasMany
    {
        return $this->hasMany(LifecycleRule::class, 'target_package_id');
    }
}
