<?php

namespace App\Models;

use App\Casts\Json;
use App\Casts\Routes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectDriver extends Pivot
{
    const STATUS_WAITING = 0;

    const STATUS_STARTED = 1;

    const STATUS_ARRIVED = 2;

    const STATUS_SKIPPED = 3;

    const STATUS_COMPLETED = 4;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates model primary keys.
     */
    protected $primaryKey = ['project_id', 'driver_id'];
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects_has_drivers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'driver_id',
        'total_distance',
        'total_time',
        'polyline_points',
        'routes',
        'stops_order',
        'position_history',
        'start_address',
        'start_lat',
        'start_lng',
        'start_time',
        'end_time',
        'utc_offset',
        'later',
        'status'
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
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'total_distance'    => 'integer',
        'total_time'        => 'integer',
        'polyline_points'   => Json::class,
        'stops_order'       => Json::class,
        'position_history'  => Json::class,
        'routes'            => Routes::class
    ];

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $keys = $this->getKeyName();

        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @param mixed $keyName
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

    /**
     * Get the project that owns the project driver.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the driver that owns the project driver.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
