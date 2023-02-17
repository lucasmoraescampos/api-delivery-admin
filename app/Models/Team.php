<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
	 * The users that belong to the team.
	 */
	public function users()
	{
        return $this->belongsToMany(User::class, 'teams_has_users', 'team_id', 'user_id');
    }

    /**
	 * The drivers that belong to the team.
	 */
	public function drivers()
	{
        return $this->belongsToMany(Driver::class, 'drivers_has_teams', 'team_id', 'driver_id');
    }

    /**
     * Get the projects for the team.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
