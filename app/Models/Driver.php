<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'start_address',
        'start_lat',
        'start_lng',
        'start_time',
        'end_time',
        'license',
        'insurance'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_lat' => 'float',
        'start_lng' => 'float'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
	 * Get the teams for the driver.
	 */
	public function teams()
	{
        return $this->belongsToMany(Team::class, 'drivers_has_teams', 'driver_id', 'team_id');
    }
}
