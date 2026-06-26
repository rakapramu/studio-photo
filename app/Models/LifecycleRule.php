<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifecycleRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'source_package_id',
        'target_package_id',
        'delay_days',
        'message_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delay_days' => 'integer',
    ];

    /**
     * Get the source package for this rule.
     */
    public function sourcePackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'source_package_id');
    }

    /**
     * Get the target package for this rule.
     */
    public function targetPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'target_package_id');
    }
}
