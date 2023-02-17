<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    const STATUS_WAITING  = 0;

    const STATUS_STARTED = 1;

    const STATUS_ARRIVED = 2;
    
    const STATUS_SKIPPED = 3;

    const IN_WINDOW_EARLY = 1;

    const IN_WINDOW_LATE = 2;

    const IN_WINDOW_ONTIME = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'driver_id',
        'order_id',
        'name',
        'phone',
        'address',
        'status',
        'lat',
        'lng',
        'in_window',
        'started_at',
        'finished_at'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_WAITING
    ];

    /**
     * Get the project that owns the stop.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the driver that owns the stop.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Check if the stop project has already been optimized
     *
     * @return string
     */
    public function getIsOptimizedAttribute()
    {
        return Project::where('id', $this->project_id)
            ->where('status', Project::STATUS_NOT_OPTIMIZED)
            ->count() == 0;
    }
}
