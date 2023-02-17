<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    const STATUS_NOT_OPTIMIZED = 0;

    const STATUS_OPTIMIZED = 1;

    const STATUS_DISPATCHED = 2;

    const STATUS_COMPLETED = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'customer_id',
        'name',
        'date',
        'status',
        'start_time',
        'end_time',
        'utc_offset'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_NOT_OPTIMIZED
    ];

    /**
     * Get the finished at.
     *
     * @return string
     */
    public function getFinishedAtAttribute()
    {
        $project_drivers = ProjectDriver::where('project_id', $this->id)->get();

        $finished_at = null;

        foreach ($project_drivers as $project_driver) {

            $route = $project_driver->routes[count($project_driver->routes) - 1];

            $completed_at = $route['arrived_at'] ?? $route['arrived_at'];

            if ($finished_at == null || $finished_at < $completed_at) {

                $finished_at = date('H:i:s', strtotime($completed_at) + $project_driver->utc_offset);

            }

        }

        return $finished_at;
    }

    /**
     * Get the team that owns the project.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the customer that owns the project.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the drivers for the project.
     */
    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'projects_has_drivers', 'project_id', 'driver_id')
            ->using(ProjectDriver::class)
            ->withPivot(
                'total_distance', 'total_time', 'polyline_points', 'routes', 'stops_order', 'position_history',
                'status', 'start_address', 'start_lat', 'start_lng', 'start_time', 'end_time', 'utc_offset'
            );
    }

    /**
     * Get the stops for the project.
     */
    public function stops()
    {
        return $this->hasMany(Stop::class);
    }
}
