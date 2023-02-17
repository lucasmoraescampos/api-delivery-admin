<?php

namespace App\Repositories;

use App\Exceptions\CustomException;
use App\Models\CustomerUser;
use App\Models\Driver;
use App\Models\DriverTeam;
use App\Models\Project;
use App\Models\ProjectDriver;
use App\Models\SmsStatus;
use App\Models\Stop;
use App\Models\TeamUser;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ProjectRepository implements ProjectRepositoryInterface
{
    protected $model;

    /**
     * ProjectRepository constructor.
     *
     * @param Project $model
     */
    public function __construct(Project $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getAll(array $attributes): array
    {
        $per_page = 15;

        $model = $this->model->with(['customer:name', 'drivers'])
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            });

        if (isset($attributes['from'])) {
            $model = $model->where('date', '>=', $attributes['from']);
        }

        if (isset($attributes['to'])) {
            $model = $model->where('date', '<=', $attributes['to']);
        }

        if (isset($attributes['name'])) {
            $model = $model->where('projects.name', 'like', "%{$attributes['name']}%");
        }

        $projects = $model->orderBy('date', 'desc')->paginate($per_page);

        $total = $projects->total();

        $items = $projects->items();

        foreach ($items as &$item) {

            $item->distance = 0;

            $item->duration = 0;

            $item->number_of_stops = 0;

            $item->number_of_drivers = count($item->drivers);

            foreach ($item->drivers as &$driver) {

                $item->distance += collect($driver->pivot['routes'])->sum('distance');

                $item->duration += collect($driver->pivot['routes'])->sum('duration');

                $item->number_of_stops += count($driver->pivot['routes']);

            }

            unset($item->drivers);

        }

        return [
            'per_page'  => $per_page,
            'total'     => $total,
            'projects'  => $items
        ];
    }

    /**
     * @param mixed $id
     * @return Project
     */
    public function getById($id): Project
    {
        return $this->model->with(['customer', 'drivers', 'stops'])
            ->where('projects.id', $id)
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->firstOrFail();
    }

    /**
     * @param array $attributes
     * @return Project
     */
    public function create(array $attributes): Project
    {
        $this->validateCreate($attributes);

        $project = $this->model->fill(Arr::only($attributes, [
            'team_id',
            'customer_id',
            'name',
            'date',
            'start_time',
            'end_time',
            'utc_offset'
        ]));

        $project->save();

        return $this->getById($project->id);
    }

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function update($id, array $attributes): Project
    {
        $this->validateUpdate($attributes);

        $project = $this->getById($id);

        $project->fill(Arr::only($attributes, [
            'team_id',
            'customer_id',
            'name',
            'date',
            'start_time',
            'end_time',
            'utc_offset'
        ]));

        $project->save();

        return $project;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function delete($id): void
    {
        $project = $this->getById($id);

        $project->delete();
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return Project
     */
    public function clone(int $id, array $attributes): Project
    {
        $this->validateClone($attributes);

        $base = $this->getById($id);

        $projects_drivers = ProjectDriver::where('project_id', $base->id)->get();

        $base->fill(Arr::only($attributes, [
            'team_id',
            'customer_id',
            'name',
            'date',
            'start_time',
            'end_time',
            'utc_offset'
        ]));

        $clone = $this->create($base->toArray());

        foreach ($projects_drivers as $project_driver) {
            $this->addProjectDriver($clone->id, $project_driver->driver_id, $project_driver->toArray());
        }
 
        return $this->getById($clone->id);
    }

    /**
     * @param int $project_id
     * @param int $driver_id
     * @param array $attributes
     * @return Project
     */
    public function addProjectDriver(int $project_id, int $driver_id, array $attributes): Project
    {
        $this->validateAddProjectDriver($attributes);

        $project = $this->getById($project_id);

        if ($project->status == Project::STATUS_COMPLETED) {
            throw new CustomException('You cannot add drivers to a completed project.', 400);
        }

        DriverTeam::where('driver_id', $driver_id)
            ->where('team_id', $project->team_id)
            ->firstOrFail();

        if (ProjectDriver::where('project_id', $project_id)->where('driver_id', $driver_id)->count() > 0) {
            throw new CustomException('This driver is already registered in this project.', 400);
        }

        ProjectDriver::create([
            'project_id'      => $project_id,
            'driver_id'       => $driver_id,
            'total_distance'  => 0,
            'total_time'      => 0,
            'polyline_points' => [],
            'routes'          => [],
            'stops_order'     => [],
            'start_address'   => $attributes['start_address'],
            'start_lat'       => $attributes['start_lat'],
            'start_lng'       => $attributes['start_lng'],
            'start_time'      => $attributes['start_time'],
            'end_time'        => $attributes['end_time'],
            'utc_offset'      => $attributes['utc_offset']
        ]);

        return $this->getById($project_id);
    }

    /**
     * @param int $project_id
     * @param int $driver_id
     * @param array $attributes
     * @return Project
     */
    public function updateProjectDriver(int $project_id, int $driver_id, array $attributes): Project
    {
        $this->validateUpdateProjectDriver($attributes);

        $project = $this->getById($project_id);

        if ($project->status == Project::STATUS_COMPLETED) {
            throw new CustomException('You cannot change drivers for a completed project.', 400);
        }

        DriverTeam::where('driver_id', $driver_id)
            ->where('team_id', $project->team_id)
            ->firstOrFail();

        $project_driver = ProjectDriver::where('project_id', $project_id)
            ->where('driver_id', $driver_id)
            ->firstOrFail();

        $project_driver->fill(Arr::only($attributes, [
            'start_address',
            'start_lat',
            'start_lng',
            'start_time',
            'end_time'
        ]));

        $project_driver->save();

        return $this->getById($project_id);
    }

    /**
     * @param int $project_id
     * @param int $driver_id
     * @return Project
     */
    public function deleteProjectDriver(int $project_id, int $driver_id): Project
    {
        $project = $this->getById($project_id);

        if ($project->status == Project::STATUS_COMPLETED) {
            throw new CustomException('You cannot delete drivers for a completed project.', 400);
        }

        ProjectDriver::where('project_id', $project_id)
            ->where('driver_id', $driver_id)
            ->firstOrFail();

        Stop::where('project_id', $project_id)
            ->where('driver_id', $driver_id)
            ->update(['driver_id' => null]);
            
        ProjectDriver::where('project_id', $project_id)
            ->where('driver_id', $driver_id)
            ->delete();

        return $this->getById($project_id);
    }

    /**
     * @param int $project_id
     * @param int $driver_id
     * @return Project
     */
    public function deleteProjectStop(int $project_id, int $stop_id): Project
    {
        $project = $this->getById($project_id);

        if ($project->status == Project::STATUS_COMPLETED) {
            throw new CustomException('You cannot delete stops for a completed project.', 400);
        }

        $stop = Stop::where('id', $stop_id)
            ->where('project_id', $project->id)
            ->firstOrFail();

        if ($stop->driver_id) {

            $projectDriver = ProjectDriver::where('project_id', $project->id)
                ->where('driver_id', $stop->driver_id)
                ->first();

            $stops = Stop::whereIn('id', $projectDriver->stops_order)->get()->keyBy('id');
            
            $order = collect();

            foreach ($projectDriver->stops_order as $index) {
                if ($stop->id != $index) {
                    $order->push($stops[$index]);
                }
            }

            $directions = $this->directions($projectDriver->driver, $order, false);

            $projectDriver->update([
                'total_distance'    => $directions['total_distance'],
                'total_time'        => $directions['total_time'],
                'polyline_points'   => $directions['polyline_points'],
                'routes'            => $directions['routes'],
                'stops_order'       => $directions['stops_order'],
                'status'            => ProjectDriver::STATUS_WAITING
            ]);

        }

        Stop::destroy($stop->id);

        return $this->getById($project->id);
    }

    /**
     * @param mixed $id
     * @return Project
     */
    public function optimize($id): Project
    {
        set_time_limit(0);

        ini_set('memory_limit', '-1');

        $project = $this->getById($id);

        if ($project->drivers->count() == 0) {
            throw new CustomException('No drivers added.', 400);
        }

        if ($project->stops->count() == 0) {
            throw new CustomException('No stops added.', 400);
        }

        if ($project->status == Project::STATUS_COMPLETED) {
            throw new CustomException('You cannot optimize a completed project.', 400);
        }

        ProjectDriver::where('project_id', $project->id)->update([
            'total_distance'    => 0,
            'total_time'        => 0,
            'polyline_points'   => [],
            'routes'            => [],
            'stops_order'       => []
        ]);

        Stop::whereIn('id', collect($project->stops)->pluck('id'))->update([
            'driver_id' => null,
            'status'    => Stop::STATUS_WAITING
        ]);

        $routes = $this->getRoutes($project);

        foreach ($routes as $route) {

            $directions = $this->directions($route->driver, $route->stops);

            Stop::whereIn('id', $route->stops->pluck('id'))->update(['driver_id' => $route->driver->id]);

            ProjectDriver::where('project_id', $project->id)
                ->where('driver_id', $route->driver->id)
                ->update([
                    'total_distance'    => $directions['total_distance'],
                    'total_time'        => $directions['total_time'],
                    'polyline_points'   => $directions['polyline_points'],
                    'routes'            => $directions['routes'],
                    'stops_order'       => $directions['stops_order'],
                    'status'            => ProjectDriver::STATUS_WAITING
                ]);
        }

        $project->status = Project::STATUS_OPTIMIZED;

        $project->save();

        return $this->getById($project->id);
    }

    /**
     * @param mixed $id
     * @return Project
     */
    public function dispatch($id): Project
    {
        $project = $this->getById($id);

        $project->status = Project::STATUS_DISPATCHED;

        $project->save();

        return $project;
    }

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function reverseRoute($id, array $attributes): Project
    {
        $this->validateReverseRoute($attributes);

        $project = $this->getById($id);

        $project_driver = ProjectDriver::where('project_id', $project->id)
            ->where('driver_id', $attributes['driver_id'])
            ->firstOrFail();

        $stops_order = collect($project_driver->stops_order)->reverse()->all();

        $stops = Stop::whereIn('id', $stops_order)->get()->keyBy('id');

        $reverse = collect();

        foreach ($stops_order as $index) {
            $reverse->push($stops[$index]);
        }

        $directions = $this->directions($project_driver->driver, $reverse, false, $project);

        $project_driver->update([
            'total_distance'    => $directions['total_distance'],
            'total_time'        => $directions['total_time'],
            'polyline_points'   => $directions['polyline_points'],
            'routes'            => $directions['routes'],
            'stops_order'       => $directions['stops_order'],
            'status'            => ProjectDriver::STATUS_WAITING
        ]);

        return $this->getById($id);
    }

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function reorder($id, array $attributes): Project
    {
        set_time_limit(0);

        $this->validateReorder($attributes);

        $project = $this->getById($id);

        ProjectDriver::where('project_id', $id)
            ->where('driver_id', $attributes['driver_id'])
            ->firstOrFail();

        if (
            Stop::whereIn('id', $attributes['stops_order'])
            ->where('project_id', '<>', $id)
            ->count() > 0
        ) {
            throw new CustomException('Some stops do not belong to this project.', 400);
        }

        if (
            Stop::whereNotIn('id', $attributes['stops_order'])
            ->where('project_id', $id)
            ->where('driver_id', $attributes['driver_id'])
            ->count() > 0
        ) {
            throw new CustomException('Stops incomplete.', 400);
        }

        $new_stop = Stop::whereIn('id', $attributes['stops_order'])
            ->where(function ($query) use ($attributes) {
                $query->where('driver_id', '<>', $attributes['driver_id'])
                    ->orWhere('driver_id', null);
            })
            ->get();

        if ($new_stop->count() > 2) {
            throw new CustomException('It is only allowed to add a new stop by reordering.', 400);
        }

        if ($new_stop->count() == 1) {

            $new_stop = $new_stop->first();

            $old_driver_id = $new_stop->driver_id;

            $new_stop->driver_id = $attributes['driver_id'];

            $new_stop->save();

            if ($old_driver_id !== null) {

                $old_driver = Driver::find($old_driver_id);

                $old_project_driver = ProjectDriver::where('project_id', $project->id)
                    ->where('driver_id', $old_driver->id)
                    ->first();

                $stops_order = $old_project_driver->stops_order;

                foreach ($stops_order as $index => $stop_id) {

                    if ($new_stop->id == $stop_id) {

                        unset($stops_order[$index]);

                        break;

                    }

                }

                $old_driver_stops = $this->getStopsByStopsOrder($stops_order);

                $directions = $this->directions($old_driver, $old_driver_stops, false, $project);

                ProjectDriver::where('project_id', $project->id)
                    ->where('driver_id', $old_driver->id)
                    ->update([
                        'total_distance'    => $directions['total_distance'],
                        'total_time'        => $directions['total_time'],
                        'polyline_points'   => $directions['polyline_points'],
                        'routes'            => $directions['routes'],
                        'stops_order'       => $directions['stops_order']
                    ]);
            }

        }

        $driver = Driver::find($attributes['driver_id']);

        $stops = $this->getStopsByStopsOrder($attributes['stops_order']);

        $directions = $this->directions($driver, $stops, false, $project);

        ProjectDriver::where('project_id', $project->id)
            ->where('driver_id', $driver->id)
            ->update([
                'total_distance'    => $directions['total_distance'],
                'total_time'        => $directions['total_time'],
                'polyline_points'   => $directions['polyline_points'],
                'routes'            => $directions['routes'],
                'stops_order'       => $directions['stops_order']
            ]);

        return $this->getById($project->id);
    }

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function swapRoute($id, array $attributes): Project
    {
        $this->validateSwapRoute($attributes);

        $project = $this->getById($id);

        $from = ProjectDriver::where('project_id', $project->id)
            ->where('driver_id', $attributes['from'])
            ->firstOrFail();

        $to = ProjectDriver::where('project_id', $project->id)
            ->where('driver_id', $attributes['to'])
            ->firstOrFail();

        $fromData = $from->only([
            'total_distance',
            'total_time',
            'polyline_points',
            'routes',
            'stops_order',
            'status',
            'start_address',
            'start_lat',
            'start_lng',
            'start_time',
            'end_time',
        ]);

        $toData = $to->only([
            'total_distance',
            'total_time',
            'polyline_points',
            'routes',
            'stops_order',
            'status',
            'start_address',
            'start_lat',
            'start_lng',
            'start_time',
            'end_time',
        ]);

        $from->fill($toData);

        $to->fill($fromData);

        $from->save();

        $to->save();

        Stop::whereIn('id', $from->stops_order)->update([
            'driver_id' => $from->driver_id
        ]);

        Stop::whereIn('id', $to->stops_order)->update([
            'driver_id' => $to->driver_id
        ]);

        return $this->getById($project->id);
    }

    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    public function sendSMS($project_id, $driver_id): void
    {
        $project = $this->getById($project_id);

        //$this->fariasSMS($project, $driver_id);
        $this->sendNexmo($project, $driver_id);
    }

    /**
     * @param mixed $project_id
     * @return void
     */
    public function sendSMSAll($project_id): void
    {
        $project = $this->getById($project_id);
        $arr     = [];

        foreach ($project->drivers as $driver)
        {
            //$arr[] = $this->fariasSMSArrayPrepare($project, $driver->id);
            $this->sendNexmo($project, $driver->id);
        }

        if( count( $arr ) > 0)
            $this->fariasSMSAll( $arr );
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function summaryExport(int $id): Collection
    {
        $project = $this->getById($id);

        return Stop::select('order_id', 'name', 'address', 'phone')
            ->where('project_id', $project->id)
            ->get();
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function solutionExport(int $id): Collection
    {
        $project = $this->getById($id);

        $solution = collect();

        foreach ($project->drivers as $driver) {

            foreach ($driver->pivot->routes as $index => $route) {

                $status = '';

                if ($route['status'] == ProjectDriver::STATUS_STARTED) {
                    $status = 'Started';
                }

                elseif ($route['status'] == ProjectDriver::STATUS_ARRIVED) {
                    $status = 'Arrived';
                }

                elseif ($route['status'] == ProjectDriver::STATUS_SKIPPED) {
                    $status = 'Skipped';
                }
                
                $solution->push([
                    'driver'        => $driver->name,
                    'step'          => $index + 1,
                    'order_id'      => $route['end_order_id'],
                    'name'          => $route['end_name'],
                    'address'       => $route['end_address'],
                    'phone'         => $route['end_phone'],
                    'status'        => $status,
                    'bags'          => $route['bags'],
                    'note'          => $route['note'],
                    'arrived_at'    => $route['arrived_at'],
                    'skipped_at'    => $route['skipped_at']
                ]);

            }

        }

        return $solution;
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function routeExport(int $id): Collection
    {
        $project = $this->getById($id);

        $route = collect();

        foreach ($project->drivers as $driver) {

            switch ($driver->pivot->status) {
                case ProjectDriver::STATUS_WAITING   : $status = 'WAITING';   break;
                case ProjectDriver::STATUS_STARTED   : $status = 'STARTED';   break;
                case ProjectDriver::STATUS_ARRIVED   : $status = 'ARRIVED';   break;
                case ProjectDriver::STATUS_SKIPPED   : $status = 'SKIPPED';   break;
                case ProjectDriver::STATUS_COMPLETED : $status = 'COMPLETED'; break;
            }

            $lastInteraction = null;

            if(in_array($status, ['ARRIVED', 'SKIPPED', 'COMPLETED'])) {

                $lastFinished = $driver->pivot->routes[count( $driver->pivot->routes ) - 1];

                if ($lastFinished['status'] == ProjectDriver::STATUS_ARRIVED) {
                    $lastInteraction = $lastFinished['arrived_at'];
                }

                if ($lastFinished['status'] == ProjectDriver::STATUS_SKIPPED) {
                    $lastInteraction = $lastFinished['skipped_at'];
                }

                if ($lastFinished['status'] == ProjectDriver::STATUS_COMPLETED) {
                    $lastInteraction = $lastFinished['arrived_at'];
                }

            }

            $route->push([
                'driver'        => $driver->name,
                'status'        => $status,
                'finished_at'   => $lastInteraction ? date('Y-m-d H:i', strtotime($lastInteraction) + $driver->pivot->utc_offset) : '',
                'start_time'    => date('Y-m-d H:i', strtotime($project->date . ' ' . $driver->pivot->start_time)),
                'end_time'      => date('Y-m-d H:i', strtotime($project->date . ' ' . $driver->pivot->end_time)),
                'start_address' => $driver->pivot->start_address,
            ]);
            
        }

        return $route;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function report(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_by_date = $this->model->with(['customer', 'drivers'])
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('date', '>=', $attributes['from'])
            ->where('date', '<=', $attributes['to'])
            ->where('status', Project::STATUS_COMPLETED)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('date');

        $data = collect();

        foreach ($projects_by_date as $date => $projects) {

            $early = 0;

            $late = 0;

            $on_time = 0;

            foreach ($projects as $project) {

                $projectsDrivers = ProjectDriver::where('project_id', $project->id)->get();

                foreach ($projectsDrivers as $projectDriver) {

                    foreach ($projectDriver->routes as $route) {

                        $time = gmdate('H:i:s', strtotime($route['arrived_at'] ?? $route['skipped_at']) + $projectDriver->utc_offset);

                        if ($time < $project->start_time) {
                            $early++;
                        } elseif ($time > $project->end_time) {
                            $late++;
                        } else {
                            $on_time++;
                        }
                    }
                }
            }

            $total = $early + $late + $on_time;

            if ($total) {

                $data->push([
                    'date' => $date,
                    'early' => [
                        'value'   => $early,
                        'percent' => round(($early * 100) / $total, 1)
                    ],
                    'late' => [
                        'value'   => $late,
                        'percent' => round(($late * 100) / $total, 1)
                    ],
                    'on_time' => [
                        'value'   => $on_time,
                        'percent' => round(($on_time * 100) / $total, 1)
                    ],
                    'total'   => $total
                ]);
            }
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function reportDetails(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_drivers = $this->model->select(
                'projects.date',
                'projects.name as project',
                'drivers.name as driver',
                'projects.start_time',
                'projects.end_time',
                'projects_has_drivers.routes',
                'projects_has_drivers.utc_offset'
            )
            ->join('projects_has_drivers', 'projects_has_drivers.project_id', 'projects.id')
            ->leftJoin('drivers', 'drivers.id', 'projects_has_drivers.driver_id')
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('projects.date', '>=', $attributes['from'])
            ->where('projects.date', '<=', $attributes['to'])
            ->where('projects.status', Project::STATUS_COMPLETED)
            ->whereNotNull('projects.start_time')
            ->whereNotNull('projects.end_time')
            ->orderBy('projects.date', 'desc')
            ->get();
            
        $data = collect();

        foreach ($projects_drivers as $project_driver) {

            $routes = json_decode($project_driver->routes);

            foreach ($routes as $index => $route) {

                $completion_time = gmdate('H:i:s', strtotime($route->arrived_at ?? $route->skipped_at) + $project_driver->utc_offset);

                $distance = $route->distance / 1609;

                $duration = $route->duration / 60;

                $in_window = 'ON TIME';

                $status = 'SKIPPED';

                if ($completion_time < $project_driver->start_time) {
                    $in_window = 'EARLY';
                } elseif ($completion_time > $project_driver->end_time) {
                    $in_window = 'LATE';
                }

                if ($route->status == ProjectDriver::STATUS_ARRIVED) {
                    $status = 'ARRIVED';
                }

                $completion_time = strtoupper(date('h:i a', strtotime($completion_time)));

                $started_at = strtoupper(gmdate('h:i a', strtotime($route->started_at) + $project_driver->utc_offset));

                $start_time = strtoupper(date('h:i a', strtotime("{$project_driver->date} {$project_driver->start_time}")));

                $end_time = strtoupper(date('h:i a', strtotime("{$project_driver->date} {$project_driver->end_time}")));

                $data->push([
                    'date'                      => $project_driver->date,
                    'project'                   => $project_driver->project,
                    'project_delivery_window'   => "{$start_time} - {$end_time}",
                    'driver'                    => $project_driver->driver,
                    'stop_number'               => $index + 1,
                    'started_at'                => $started_at,
                    'completion_time'           => $completion_time,
                    'in_window'                 => $in_window,
                    'order_id'                  => $route->end_order_id,
                    'status'                    => $status,
                    'stop'                      => $route->end_name,
                    'phone'                     => $route->end_phone,
                    'address'                   => $route->end_address,
                    'distance'                  => $distance,
                    'duration'                  => $duration
                ]);
            }
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return float
     */
    public function reportPercent(array $attributes): float
    {
        $this->validateReport($attributes);

        $projects_interval_total = $this->model->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('date', '>=', $attributes['from'])
            ->where('date', '<=', $attributes['to'])
            ->where('status', Project::STATUS_COMPLETED)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->count();

        $completed_projects_total = $this->model->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('status', Project::STATUS_COMPLETED)
            ->count();

        return round(($projects_interval_total * 100) / $completed_projects_total, 1);
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function bagsReport(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_by_date = $this->model->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('date', '>=', $attributes['from'])
            ->where('date', '<=', $attributes['to'])
            ->where('status', Project::STATUS_COMPLETED)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('date');

        $data = collect();

        foreach ($projects_by_date as $date => $projects) {

            $bags = 0;

            foreach ($projects as $project) {

                $projectsDrivers = ProjectDriver::where('project_id', $project->id)->get();

                foreach ($projectsDrivers as $projectDriver) {

                    foreach ($projectDriver->routes as $route) {

                        $bags += $route['bags'] ?? 0;
                    }
                }
            }

            $data->push([
                'date' => $date,
                'bags' => $bags
            ]);
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function bagsReportDetails(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_drivers = $this->model->select(
                'projects.date',
                'projects.name as project',
                'drivers.name as driver',
                'projects.start_time',
                'projects.end_time',
                'projects_has_drivers.routes'
            )
            ->join('projects_has_drivers', 'projects_has_drivers.project_id', 'projects.id')
            ->leftJoin('drivers', 'drivers.id', 'projects_has_drivers.driver_id')
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })            
            ->where('projects.date', '>=', $attributes['from'])
            ->where('projects.date', '<=', $attributes['to'])
            ->where('projects.status', Project::STATUS_COMPLETED)
            ->whereNotNull('projects.start_time')
            ->whereNotNull('projects.end_time')
            ->orderBy('projects.date', 'desc')
            ->get();

        $data = collect();

        foreach ($projects_drivers as $project_driver) {

            $routes = json_decode($project_driver->routes);

            $bags = 0;

            foreach ($routes as $route) {

                $bags += $route->bags ?? 0;
            }

            if ($bags) {

                $data->push([
                    'date'    => $project_driver->date,
                    'project' => $project_driver->project,
                    'driver'  => $project_driver->driver,
                    'bags'    => $bags
                ]);
            }
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function driversReport(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_by_date = $this->model->select(
                'projects.date',
                'drivers.name as driver',
                'projects_has_drivers.routes',
                'projects_has_drivers.utc_offset'
            )
            ->join('projects_has_drivers', 'projects_has_drivers.project_id', 'projects.id')
            ->leftJoin('drivers', 'drivers.id', 'projects_has_drivers.driver_id')
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('projects.date', '>=', $attributes['from'])
            ->where('projects.date', '<=', $attributes['to'])
            ->where('projects.status', Project::STATUS_COMPLETED)
            ->whereNotNull('projects.start_time')
            ->whereNotNull('projects.end_time')
            ->orderBy('projects.date', 'desc')
            ->get()
            ->groupBy('date');

        $data = collect();

        foreach ($projects_by_date as $projects_drivers) {

            $sum = collect();

            foreach ($projects_drivers as $project_driver) {

                $routes = json_decode($project_driver->routes);

                $first = gmdate('Y-m-d H:i:s', strtotime($routes[0]->started_at) + $project_driver->utc_offset);

                $last = gmdate('Y-m-d H:i:s', strtotime(end($routes)->arrived_at ?? end($routes)->skipped_at) + $project_driver->utc_offset);

                $sum->push((count($routes) * 60) / ((strtotime($last) - strtotime($first)) / 60));
            }

            $data->push([
                'date'    => $project_driver->date,
                'avg'     => round($sum->avg(), 1)
            ]);
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function driversReportDetails(array $attributes): Collection
    {
        $this->validateReport($attributes);

        $projects_drivers = $this->model->select(
                'projects.date',
                'projects.name as project',
                'drivers.name as driver',
                'projects_has_drivers.routes',
                'projects_has_drivers.utc_offset'
            )
            ->join('projects_has_drivers', 'projects_has_drivers.project_id', 'projects.id')
            ->leftJoin('drivers', 'drivers.id', 'projects_has_drivers.driver_id')
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->where('projects.date', '>=', $attributes['from'])
            ->where('projects.date', '<=', $attributes['to'])
            ->where('projects.status', Project::STATUS_COMPLETED)
            ->whereNotNull('projects.start_time')
            ->whereNotNull('projects.end_time')
            ->orderBy('projects.date', 'desc')
            ->get();

        $data = collect();

        foreach ($projects_drivers as $project_driver) {

            $routes = json_decode($project_driver->routes);

            $first = gmdate('Y-m-d H:i:s', strtotime($routes[0]->started_at) + $project_driver->utc_offset);

            $last = gmdate('Y-m-d H:i:s', strtotime(end($routes)->arrived_at ?? end($routes)->skipped_at) + $project_driver->utc_offset);

            $avg = round((count($routes) * 60) / ((strtotime($last) - strtotime($first)) / 60), 1);

            $data->push([
                'date'    => $project_driver->date,
                'project' => $project_driver->project,
                'driver'  => $project_driver->driver,
                'avg'     => $avg
            ]);
        }

        return $data;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function deliveries(array $attributes): array
    {
        $this->validateDeliveries($attributes);

        $stops = Stop::with(['project', 'driver'])
            ->whereHas('project.team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            });

        if (isset($attributes['date'])) {

            $stops = $stops->whereHas('project', function (Builder $query) use ($attributes) {
                $query->where('date', $attributes['date']);
            });

        }
        
        if (isset($attributes['order_id'])) {

            $stops = $stops->where('order_id', 'like', "%{$attributes['order_id']}%");

        }

        $per_page = isset($attributes['limit']) ? $attributes['limit'] : 5;

        $stops = $stops->whereIn('status', [Stop::STATUS_ARRIVED, Stop::STATUS_SKIPPED])
            ->orderBy('finished_at', 'desc')
            ->paginate($per_page);

        foreach ($stops->items() as &$stop) {

            $projectDriver = ProjectDriver::where('project_id', $stop->project_id)
                ->where('driver_id', $stop->driver_id)
                ->first();

            $route = collect($projectDriver->routes)->where('end_id', $stop->id)->first();

            $stop->utc_offset = $projectDriver->utc_offset;

            $stop->route = [
                'image'         => $route['image'],
                'note'          => $route['note'],
                'bags'          => $route['bags']
            ];

        }

        return [
            'per_page'  => $per_page,
            'total'     => $stops->total(),
            'stops'     => $stops->items()
        ];
    }

    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    private function sendNexmo($project, $driver_id)
    {
        $driver = Driver::where('id', $driver_id)->firstOrFail();

        $message = "{$project->name}: https://driver.fariaslgx.com/" . md5($driver_id) . '/stops/route/' . md5($project->id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('nexmo.key')
        ])->post('https://api.nexmo.com/v0.1/messages', [
            'from' => [
                'type'   => 'sms',
                'number' => '18332685694'
            ],
            'to' => [
                'type'   => 'sms',
                'number' => $driver->phone
            ],
            'message' => [
                'content' => [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new CustomException($response['detail'], 400);
        }

        SmsStatus::create([
            'from'         => '18332685694',
            'to'           => $driver->phone,
            'message_uuid' => $response['message_uuid']
        ]);
    }

    /**
     * @param array $stops_order
     * @return Collection
     */
    private function getStopsByStopsOrder(array $stops_order): Collection
    {
        $stops      = Stop::whereIn('id', $stops_order)->get();
        $stops_list = [];

        foreach ($stops as $stop) {
            $stops_list[$stop->id] = $stop;
        }

        $stops = collect();

        foreach ($stops_order as $stop_id) {
            $stops->push($stops_list[$stop_id]);
        }

        return $stops;
    }

    /**
     * @param Project $project
     * @return Collection
     */
    private function getRoutes(Project $project): Collection
    {
        $routes = collect();

        $drivers = $project->drivers;

        $stops = $project->stops;

        $length = ceil(count($stops) / count($drivers));

        foreach ($drivers as $driver) {

            $first = $this->getFirst($driver, $stops);

            if ($first) {

                $collect = collect([$first]);

                $items   = $stops->whereNotIn('id', $collect->pluck('id'))->values();

                while ($collect->count() < $stops->count()) {

                    foreach ($items as &$item) {
                        $item->distance = distance($collect->last()->lat, $collect->last()->lng, $item->lat, $item->lng);
                    }

                    $collect->push($items->sortBy('distance')->first());

                    $items = $items->whereNotIn('id', $collect->pluck('id'))->values();
                }

                $slice = $this->sliceStops($driver, $collect, $project->id, $length);

                $stops = $stops->whereNotIn('id', $slice->pluck('id'))->values();

                $routes->push((object) [
                    'driver'    => $driver,
                    'stops'     => $slice
                ]);

            }

        }

        return $routes;
    }

    /**
     * @param Driver $driver
     * @param Collection $stops
     * @return Stop
     */
    private function getFirst(Driver $driver, Collection $stops): ?Stop
    {
        foreach ($stops as &$stop) {

            if ($driver->pivot->start_lat == 'null' || $driver->pivot->start_lng == 'null') {

                throw new CustomException('Driver "' . $driver['name'] . '" : position not found', 404);

            }

            $stop->distance = distance($driver->pivot->start_lat, $driver->pivot->start_lng, $stop->lat, $stop->lng);
        
        }

        $first = $stops->sortBy('distance')->first();

        if ($stops->count() == 1 && $first) {
            return $first;
        }

        while ($stops->count() > 0) {

            $stops = $stops->where('id', '!=', $first->id);

            foreach ($stops as &$stop) {
                $stop->distance = distance($first->lat, $first->lng, $stop->lat, $stop->lng);
            }

            $next = $stops->sortBy('distance')->first();

            if ($next) {
                return $first;
            }

            $first = $next;

        }

        return null;
    }

    /**
     * @param Driver $driver
     * @param Collection $stops
     * @param int $project_id
     * @param int $length
     * @return Collection
     */
    private function sliceStops(Driver $driver, Collection $stops, int $project_id, int $length): Collection
    {
        $project_driver = ProjectDriver::where('project_id', $project_id)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        if ($project_driver->start_time > $project_driver->end_time) {

            $start = strtotime("2021-07-22 {$project_driver->start_time}");

            $end = strtotime("2021-07-23 {$project_driver->end_time}");

            $total_time = $end - $start;

        } else {

            $start = strtotime("2021-07-23 {$project_driver->start_time}");

            $end = strtotime("2021-07-23 {$project_driver->end_time}");

            $total_time = $end - $start;

        }

        $order = collect();

        $origin = "{$driver->pivot->start_lat},{$driver->pivot->start_lng}";

        $waypoints = [];

        $list = [];

        $duration = 0;

        foreach ($stops as $index => $stop) {

            $list[] = $stop;

            $waypoints[] = "{$stop->lat},{$stop->lng}";

            if (($index + 1) % 25 == 0 || ($index + 1) == $stops->count()) {
                
                $destination = array_pop($waypoints);

                if (count($waypoints) > 0) {

                    array_unshift($waypoints, 'optimize:true');

                    $waypoints = implode('|', $waypoints);

                } else {

                    $waypoints = null;

                }

                $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin'      => $origin,
                    'destination' => $destination,
                    'waypoints'   => $waypoints,
                    'mode'        => 'driving',
                    'key'         => config('googlemaps.key')
                ])->json();

                $finished = false;

                $last = (object) null;

                foreach ($response['routes'][0]['legs'] as $index => $leg) {

                    if (!$finished) {

                        if ($leg['distance']['value'] == 0) {

                            $order->push($list[$index]);

                        } elseif ($duration + $leg['duration']['value'] + 300 <= $total_time) {

                            $order->push($list[$index]);

                            $duration += $leg['duration']['value'] + 300;

                        } else {

                            return $order;

                        }

                        if ($order->count() >= $length) {

                            $finished = true;

                            $last = $list[$index];

                        }

                    } else {
                        
                        if ($last->lat == $list[$index]->lat && $last->lng == $list[$index]->lng) {

                            $order->push($list[$index]);

                        }

                        else {

                            return $order;

                        }

                    }

                }

                $origin = $destination;

                $waypoints = [];

                $list = [];

            }

        }

        return $order;
    }

    /**
     * @param Driver $driver
     * @param Collection $stops
     * @param bool $optimize
     * @return array
     */
    private function directions(Driver $driver, Collection $stops, bool $optimize = true, $project = null): array
    {
        $start_lat = null;

        $start_lng = null;

        $start_address = null;

        if (isset($driver->pivot)) {

            $start_lat = $driver->pivot->start_lat;

            $start_lng = $driver->pivot->start_lng;

            $start_address = $driver->pivot->start_address;

        } elseif ($project) {

            $pd = ProjectDriver::where('project_id', $project->id)
                ->where('driver_id', $driver->id)
                ->first()
                ->toArray();

            $start_lat = $pd['start_lat'];

            $start_lng = $pd['start_lng'];

            $start_address = $pd['start_address'];

        }

        $divider = $optimize ? 25 : 10;

        $origin = "{$start_lat},{$start_lng}";

        $stops_list = [];

        $waypoints = [];

        $directions = [
            'total_distance'  => 0,
            'total_time'      => 0,
            'polyline_points' => [],
            'routes'          => [],
            'stops_order'     => []
        ];

        foreach ($stops as $index => $stop) {

            $stops_list[] = $stop;

            $waypoints[]  = "{$stop->lat},{$stop->lng}";

            if (($index + 1) % $divider == 0 || ($index + 1) == $stops->count()) {

                $destination = array_pop($waypoints);

                if (count($waypoints) > 0) {

                    if ($optimize) {

                        array_unshift($waypoints, 'optimize:true');

                    }

                    $waypoints = implode('|', $waypoints);

                } else {

                    $waypoints = null;

                }

                $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin'      => $origin,
                    'destination' => $destination,
                    'waypoints'   => $waypoints,
                    'mode'        => 'driving',
                    'key'         => config('googlemaps.key')
                ])->json();

                if (count($response['routes']) > 0) {

                    $ordered_stops = [];

                    foreach ($response['routes'][0]['waypoint_order'] as $index) {

                        $ordered_stops[] = $stops_list[$index];

                        $directions['stops_order'][] = $stops_list[$index]->id;

                    }

                    $ordered_stops[] = $stop;

                    $directions['stops_order'][] = $stop->id;

                    $directions['polyline_points'][] = $response['routes'][0]['overview_polyline']['points'];

                    foreach ($response['routes'][0]['legs'] as $index => $leg) {

                        $downtime = 0;

                        if ($leg['distance']['value'] > 0) {

                            $downtime = 300;

                            $directions['total_distance'] += $leg['distance']['value'];

                            $directions['total_time'] += $leg['duration']['value'] + 300;

                        }

                        $directions['routes'][] = [
                            'distance'       => $leg['distance']['value'],
                            'duration'       => $leg['duration']['value'],
                            'downtime'       => $downtime,
                            'start_id'       => $index == 0 ? $driver->id    : $ordered_stops[$index - 1]->id,
                            'start_order_id' => $index == 0 ? null           : $ordered_stops[$index]->order_id,
                            'start_name'     => $index == 0 ? $driver->name  : $ordered_stops[$index - 1]->name,
                            'start_phone'    => $index == 0 ? $driver->phone : $ordered_stops[$index - 1]->phone,
                            'start_address'  => $index == 0 ? $start_address : $ordered_stops[$index - 1]->address,
                            'start_lat'      => $index == 0 ? $start_lat     : $ordered_stops[$index - 1]->lat,
                            'start_lng'      => $index == 0 ? $start_lng     : $ordered_stops[$index - 1]->lng,
                            'end_id'         => $ordered_stops[$index]->id,
                            'end_order_id'   => $ordered_stops[$index]->order_id,
                            'end_name'       => $ordered_stops[$index]->name,
                            'end_phone'      => $ordered_stops[$index]->phone,
                            'end_address'    => $ordered_stops[$index]->address,
                            'end_lat'        => $ordered_stops[$index]->lat,
                            'end_lng'        => $ordered_stops[$index]->lng,
                            'image'          => null,
                            'note'           => null,
                            'bags'           => null,
                            'status'         => ProjectDriver::STATUS_WAITING,
                            'started_at'     => null,
                            'arrived_at'     => null,
                            'skipped_at'     => null
                        ];
                    }
                }

                $origin = $destination;

                $waypoints = [];

                $stops_list = [];
            }
        }

        return $directions;
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateCreate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name'          => 'required|string|max:100',
            'date'          => 'required|date_format:Y-m-d',
            'start_time'    => 'required|string',
            'end_time'      => 'required|string',
            'utc_offset'    => 'required|numeric',
            'team_id'       => [
                'required',
                function ($attribute, $value, $fail) {

                    $count = TeamUser::where('team_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected team id is invalid');
                    }
                }
            ],
            'customer_id'   => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $count = CustomerUser::where('customer_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected customer id is invalid');
                    }
                }
            ]
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateUpdate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name'          => 'nullable|string|max:100',
            'date'          => 'nullable|date_format:Y-m-d',
            'start_time'    => 'nullable|string',
            'end_time'      => 'nullable|string',
            'utc_offset'    => 'required|numeric',
            'team_id'       => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $count = TeamUser::where('team_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected team id is invalid');
                    }
                }
            ],
            'customer_id'   => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $count = CustomerUser::where('customer_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected customer id is invalid');
                    }
                }
            ]
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateClone(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name'          => 'nullable|string|max:100',
            'date'          => 'nullable|date_format:Y-m-d',
            'start_time'    => 'nullable|string',
            'end_time'      => 'nullable|string',
            'utc_offset'    => 'nullable|numeric',
            'team_id'       => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $count = TeamUser::where('team_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected team id is invalid');
                    }
                }
            ],
            'customer_id'   => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $count = CustomerUser::where('customer_id', $value)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($count == 0) {
                        $fail('The selected customer id is invalid');
                    }
                }
            ]
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateAddProjectDriver(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'start_address'     => 'required|string|max:255',
            'start_lat'         => 'required|numeric',
            'start_lng'         => 'required|numeric',
            'start_time'        => 'required|string',
            'end_time'          => 'required|string',
            'utc_offset'        => 'required|numeric'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateUpdateProjectDriver(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'start_address'     => 'nullable|string|max:255',
            'start_lat'         => 'nullable|numeric',
            'start_lng'         => 'nullable|numeric',
            'start_time'        => 'nullable|string',
            'end_time'          => 'nullable|string'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateReverseRoute(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'driver_id' => 'required|numeric'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateReorder(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'driver_id'     => 'required|numeric',
            'stops_order'   => 'required|array'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateSwapRoute(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'from'      => 'required|numeric',
            'to'        => 'required|numeric'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateReport(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'from'  => 'required|date|date_format:Y-m-d',
            'to'    => 'required|date|date_format:Y-m-d'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateDeliveries(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'date'      => 'nullable|date|date_format:Y-m-d',
            'order_id'  => 'nullable|string'
        ]);

        $validator->validate();
    }


















    /**
     * @return Collection
     */
    public function getByCustomer(): Collection
    {
        return Project::with('drivers')
            ->select('projects.*', 'customers.name as customer')
            ->leftJoin('customers', 'customers.id', '=', 'projects.customer_id')
            ->where('customer_id', Auth::id())
            ->whereIn('projects.status', [2, 3])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * @param mixed $id
     * @return Project
     */
    public function getByCustomerId($id): Project
    {
        $project = Project::with(['drivers', 'stops'])
            ->select('projects.*', 'customers.name as customer')
            ->leftJoin('customers', 'customers.id', '=', 'projects.customer_id')
            ->where('projects.id', $id)
            ->where('customer_id', Auth::id())
            ->whereIn('projects.status', [2, 3])
            ->first();

        if (!$project)
            throw new CustomException('Project not found.', 404);

        return $project;
    }



    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    private function fariasSMS($project, $driver_id)
    {
        $driver  = Driver::where('id', $driver_id)->firstOrFail();
        $message = "{$project->name}: https://driver.fariaslgx.com/" . md5($driver_id) . '/stops/route/' . md5($project->id);
        $url     = 'https://sms.fariaslgx.com/api/queue';
        $data    = [
            'name'    => $driver->name,
            'target'  => $driver->phone,
            'message' => $message,
        ];

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode( $response, true );

        if ( $resp['success'] != true )
            throw new CustomException('Error sending SMS', 400);
    }



    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    private function fariasSMSArrayPrepare($project, $driver_id)
    {
        $driver  = Driver::where('id', $driver_id)->firstOrFail();
        $message = "{$project->name}: https://driver.fariaslgx.com/" . md5($driver_id) . '/stops/route/' . md5($project->id);

        return [
            'name'    => $driver->name,
            'target'  => $driver->phone,
            'message' => $message,
        ];
    }


    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    private function fariasSMSAll($arr)
    {
        $url     = 'https://sms.fariaslgx.com/api/queue';
        $data    = [
            'array' => $arr
        ];

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode( $response, true );

        if ( $resp['success'] != true )
            throw new CustomException('Error sending SMS', 400);
    }



}
