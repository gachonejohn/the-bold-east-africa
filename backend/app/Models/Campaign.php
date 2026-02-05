<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'company',
        'type',
        'status',
        'price',
        'invoice',
        'image',
        'target_url',
        'start_date',
        'end_date',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Add camelCase accessors for frontend compatibility
    protected $appends = ['targetUrl', 'startDate', 'endDate'];

    public function getTargetUrlAttribute()
    {
        return $this->attributes['target_url'] ?? null;
    }

    public function getStartDateAttribute()
    {
        return $this->attributes['start_date'] ?? null;
    }

    public function getEndDateAttribute()
    {
        return $this->attributes['end_date'] ?? null;
    }
}
