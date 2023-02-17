<?php

namespace App\Repositories;

use App\Exceptions\CustomException;
use App\Models\Stop;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StopsImport;
use App\Models\Project;
use App\Models\ProjectDriver;
use App\Repositories\Contracts\StopRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StopRepository implements StopRepositoryInterface
{
    protected $model;

    /**
     * StopRepository constructor.
     *
     * @param Stop $model
     */
    public function __construct(Stop $model)
    {
        $this->model = $model;
    }

    /**
     * @param mixed $id
     * @return Stop
     */
    public function getById($id): Stop
    {
        return $this->model->where('id', $id)
            ->whereHas('project.team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->firstOrFail();
    }

    /**
     * @param array $attributes
     * @return Stop
     */
    public function create(array $attributes): Stop
    {
        $this->validateCreate($attributes);

        $stop = $this->model->fill(Arr::only($attributes, [
            'project_id',
            'order_id',
            'name',
            'phone',
            'address',
            'lat',
            'lng'
        ]));

        $stop->save();

        return $stop;
    }

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Stop
     */
    public function update($id, array $attributes): Stop
    {
        $this->validateUpdate($attributes);

        $stop = $this->getById($id);
        
        $stop->fill(Arr::only($attributes, [
            'order_id',
            'name',
            'phone'
        ]));

        // if ($stop->is_optimized) {

        //     if (isset($attributes['status'])) {

        //         if ($attributes['status'] == Stop::STATUS_STARTED) {

        //             // $this->startStop($stop, null, null);

        //         }

        //         else {

        //             $stop->finished_at = gmdate('Y-m-d H:i:s', strtotime($attributes['datetime']));

        //             $this->finishStop($stop, Arr::only($attributes, [
        //                 'status',
        //                 'datetime',
        //                 'note',
        //                 'image',
        //                 'bags'
        //             ]));

        //         }

        //     }

        // }

        // else {

            // Update address only if stop was not optimized
            
            $stop->fill(Arr::only($attributes, [
                'address',
                'lat',
                'lng'
            ]));

        // }

        $stop->save();

        return $stop;
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function columnNames(array $attributes): Collection
    {
        $this->validateColumnNames($attributes);

        return Excel::toCollection(new StopsImport(), $attributes['file'])[0][0]->keys();
    }

    /**
     * @param array $attributes
     * @return Collection
     */
    public function import(array $attributes): Collection
    {
        set_time_limit(0);
        
        $this->validateImport($attributes);

        try {

            Excel::import(new StopsImport($attributes['project_id'], $attributes['column_names']), $attributes['file']);

        } catch (ValidationException $e) {

            throw new CustomException($e->errors()[0], 200);

        }

        return Project::find($attributes['project_id'])->stops;
    }

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param string $started_at
     * @return void
     */
    public function start(Stop &$stop, ProjectDriver &$project_driver, string $started_at): void
    {
        $index = array_search($stop->id, $project_driver->stops_order);

        $routes = $project_driver->routes;

        $routes[$index]['status'] = ProjectDriver::STATUS_STARTED;

        $routes[$index]['started_at'] = date('Y-m-d\TH:i:s\Z', strtotime($started_at));

        $project_driver->status = ProjectDriver::STATUS_STARTED;

        $project_driver->routes = $routes;

        $project_driver->save();

        $stop->status = Stop::STATUS_STARTED;

        $stop->started_at = $started_at;

        $stop->save();
    }

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function arrive(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void
    {
        $project = Project::find($project_driver->project_id);

        $index = array_search($stop->id, $project_driver->stops_order);

        $routes = $project_driver->routes;

        $routes[$index]['status'] = ProjectDriver::STATUS_ARRIVED;

        $routes[$index]['arrived_at'] = date('Y-m-d\TH:i:s\Z', strtotime($attributes['arrived_at']));

        $routes[$index]['bags'] = $attributes['bags'];

        $routes[$index]['note'] = $attributes['note'];

        $routes[$index]['image'] = fileUpload($attributes['image']);

        if (collect($routes)->where('status', ProjectDriver::STATUS_WAITING)->count() == 0) {

            $project_driver->status = ProjectDriver::STATUS_COMPLETED;

            if (
                ProjectDriver::where('project_id', $project_driver->project_id)
                ->where('driver_id', '<>', $project_driver->driver_id)
                ->where('status', '<>', ProjectDriver::STATUS_COMPLETED)
                ->count() == 0
            ) {

                $project->status = Project::STATUS_COMPLETED;

                $project->save();

            }

        }

        $project_driver->routes = $routes;

        $project_driver->save();

        $stop->status = Stop::STATUS_ARRIVED;

        $stop->finished_at = $attributes['arrived_at'];

        $time = date('H:i:s', strtotime($attributes['arrived_at']) + $project_driver->utc_offset);

        if ($time < $project->start_time) {
            $stop->in_window = Stop::IN_WINDOW_EARLY;
        }

        elseif ($time > $project->end_time) {
            $stop->in_window = Stop::IN_WINDOW_LATE;
        }
        
        else {
            $stop->in_window = Stop::IN_WINDOW_ONTIME;
        }

        $stop->save();
    }

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function skip(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void
    {
        $project = Project::find($project_driver->project_id);

        $index = array_search($stop->id, $project_driver->stops_order);

        $routes = $project_driver->routes;

        $routes[$index]['status'] = ProjectDriver::STATUS_SKIPPED;

        $routes[$index]['skipped_at'] = date('Y-m-d\TH:i:s\Z', strtotime($attributes['skipped_at']));

        $routes[$index]['note'] = $attributes['note'];

        if (collect($routes)->where('status', ProjectDriver::STATUS_WAITING)->count() == 0) {

            $project_driver->status = ProjectDriver::STATUS_COMPLETED;

            if (
                ProjectDriver::where('project_id', $project_driver->project_id)
                ->where('driver_id', '<>', $project_driver->driver_id)
                ->where('status', '<>', ProjectDriver::STATUS_COMPLETED)
                ->count() == 0
            ) {

                $project->status = Project::STATUS_COMPLETED;

                $project->save();

            }

        }

        $project_driver->routes = $routes;

        $project_driver->save();

        $stop->status = Stop::STATUS_SKIPPED;

        $stop->finished_at = $attributes['skipped_at'];

        $time = date('H:i:s', strtotime($attributes['skipped_at']) + $project_driver->utc_offset);

        if ($time < $project->start_time) {
            $stop->in_window = Stop::IN_WINDOW_EARLY;
        }

        elseif ($time > $project->end_time) {
            $stop->in_window = Stop::IN_WINDOW_LATE;
        }
        
        else {
            $stop->in_window = Stop::IN_WINDOW_ONTIME;
        }

        $stop->save();
    }

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function changeStatus(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void
    {
        $index = array_search($stop->id, $project_driver->stops_order);

        $routes = $project_driver->routes;

        $routes[$index]['status'] = ProjectDriver::STATUS_ARRIVED;

        $routes[$index]['bags'] = $attributes['bags'];

        $routes[$index]['note'] = $attributes['note'];

        $routes[$index]['image'] = fileUpload($attributes['image']);

        $routes[$index]['arrived_at'] = date('Y-m-d\TH:i:s\Z', strtotime($attributes['arrived_at']));

        $routes[$index]['skipped_at'] = null;

        $project_driver->routes = $routes;

        $project_driver->save();

        $stop->status = Stop::STATUS_ARRIVED;

        $stop->finished_at = $attributes['arrived_at'];

        $stop->save();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateCreate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'order_id'      => 'required|numeric',
            'name'          => 'required|string|max:200',
            'phone'         => 'required|string|max:20',
            'address'       => 'required|string|max:255',
            'lat'           => 'required|numeric',
            'lng'           => 'required|numeric',
            'project_id'    => [
                'required',
                function ($attribute, $value, $fail) {

                    $count = Project::where('id', $value)
                        ->whereHas('team.users', function (Builder $query) {
                            $query->where('id', Auth::id());
                        })
                        ->count();

                    if ($count == 0) {
                        $fail('The selected project id is invalid');
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
            'order_id'      => 'sometimes|numeric',
            'name'          => 'sometimes|string|max:200',
            'phone'         => 'sometimes|string|max:20',
            'address'       => 'sometimes|string|max:500',
            'lat'           => 'required_with:address|numeric',
            'lng'           => 'required_with:address|numeric',
            'status'        => 'sometimes|numeric|in:1,2,3',
            'datetime'      => 'required_with:status|date',
            'note'          => 'sometimes|string',
            'image'         => 'sometimes|string',
            'bags'          => 'sometimes|numeric'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateColumnNames(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'file' => 'required|file|mimes:xls,xlsx'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateImport(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'column_names.order_id'     => 'required|string',
            'column_names.first_name'   => 'required|string',
            'column_names.last_name'    => 'nullable|string',
            'column_names.street'       => 'required|string',
            'column_names.city'         => 'nullable|string',
            'column_names.zip_code'     => 'nullable|string',
            'column_names.phone'        => 'required|string',
            'file'                      => 'required|file|mimes:xls,xlsx',
            'project_id'                => [
                'required',
                function ($attribute, $value, $fail) {

                    $count = Project::where('id', $value)
                        ->whereHas('team.users', function (Builder $query) {
                            $query->where('id', Auth::id());
                        })
                        ->count();

                    if ($count == 0) {
                        $fail('The selected project id is invalid');
                    }

                }
            ]
        ]);

        $validator->validate();
    }
}
