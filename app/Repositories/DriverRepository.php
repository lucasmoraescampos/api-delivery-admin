<?php

namespace App\Repositories;

use App\Models\Driver;
use App\Models\DriverTeam;
use App\Models\ProjectDriver;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DriverRepository implements DriverRepositoryInterface
{
    protected $model;

    /**
     * DriverRepository constructor.
     *
     * @param Driver $model
     */
    public function __construct(Driver $model)
    {
        $this->model = $model;
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->with('teams')
            ->whereHas('teams.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * @param int $team_id
     * @return Collection
     */
    public function getByTeam(int $team_id): Collection
    {
        return $this->model->with('teams')
            ->whereHas('teams', function (Builder $query) use ($team_id) {
                $query->where('id', $team_id);
            })
            ->whereHas('teams.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * @param mixed $id
     * @return Driver
     */
    public function getById($id): Driver
    {
        return $this->model->with('teams')
            ->where('id', $id)
            ->whereHas('teams.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->firstOrFail();
    }

    /**
     * @param mixed $driver_hash
     * @return Collection
     */
    public function getProjectsByDriver($driver_hash): Collection
    {
        return ProjectDriver::select('project_id', 'routes', 'total_distance', 'total_time')
            ->with('project:id,name,created_at')
            ->whereRaw("md5(driver_id) = '{$driver_hash}'")
            ->whereRaw(" routes != '[]' ")
            ->orderBy('project_id', 'desc')
            ->get();
    }

    /**
     * @param string $project_hash
     * @param string $driver_hash
     * @return ProjectDriver
     */
    public function getProjectDriver(string $project_hash, string $driver_hash): ProjectDriver
    {
        return ProjectDriver::whereRaw("md5(project_id) = '{$project_hash}'")
            ->whereRaw("md5(driver_id) = '{$driver_hash}'")
            ->firstOrFail();
    }

    /**
     * @param array $attributes
     * @return Driver
     */
    public function create(array $attributes): Driver
    {
        $driver = Driver::create(Arr::only($attributes, [
            'name',
            'phone',
            'start_address',
            'start_lat',
            'start_lng',
            'start_time',
            'end_time'
        ]));

        $driver_teams = [];

        foreach ($attributes['teams'] as $team_id) {

            $driver_teams[] = [
                'driver_id' => $driver->id,
                'team_id'   => $team_id
            ];

        }

        DriverTeam::insert($driver_teams);

        $driver->load('teams');

        return $driver;
    }

    /**
     * @param array $attributes
     * @return Driver
     */
    public function update($id, array $attributes): Driver
    {
        $driver = $this->getById($id);

        $driver->fill(Arr::only($attributes, [
            'name',
            'phone',
            'start_address',
            'start_lat',
            'start_lng',
            'start_time',
            'end_time'
        ]));

        $driver->save();

        $driver_teams = [];

        if (isset($attributes['teams'])) {

            foreach ($attributes['teams'] as $team_id) {

                $driver_teams[] = [
                    'driver_id' => $driver->id,
                    'team_id'   => $team_id
                ];

            }

            DriverTeam::where('driver_id', $driver->id)->delete();

            DriverTeam::insert($driver_teams);

            $driver->load('teams');

        }

        return $driver;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function delete($id): void
    {
        $driver = $this->getById($id);

        $driver->delete();
    }
}
