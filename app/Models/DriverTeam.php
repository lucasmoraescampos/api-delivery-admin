<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverTeam extends Model
{
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
    protected $primaryKey = ['driver_id', 'team_id'];
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'drivers_has_teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'driver_id',
        'team_id'
    ];
}
