<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Models\Driver;
use App\Models\ProjectDriver;
use App\Models\Stop;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\StopRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DriverService
{
    private $driverRepository;
    private $stopRepository;

    public function __construct(
        DriverRepositoryInterface $driverRepository,
        StopRepositoryInterface $stopRepository)
    {
        $this->driverRepository = $driverRepository;
        $this->stopRepository   = $stopRepository;
    }

    /**
     * @param string $driver_hash
     * @return Collection
     */
    public function getProjectsByDriver(string $driverHash): Collection
    {
        $projects = $this->driverRepository->getProjectsByDriver($driverHash);

        if ($projects->count() == 0) {
            throw new CustomException('Driver not found.', 404);
        }

        return $projects->map(function ($data) {
            return [
                'hash'           => md5($data->project->id),
                'name'           => $data->project->name,
                'created_at'     => $data->project->created_at,
                'routes'         => $data->routes,
                'total_distance' => $data->total_distance,
                'total_time'     => $data->total_time,
            ];
        });
    }

    /**
     * @param string $project_hash
     * @param string $driver_hash
     * @return array
     */
    public function getProjectDriver(string $project_hash, string $driver_hash): array
    {
        $project_driver = $this->driverRepository->getProjectDriver($project_hash, $driver_hash);
    
        return $this->serializeProjectDriver($project_driver);
    }

    /**
     * @param array $attributes
     * @return Driver
     */
    public function create(array $attributes): Driver
    {
        $validator = Validator::make($attributes, [
            'name'          => 'required|string|max:100',
            'phone'         => 'required|string|max:40',
            'start_address' => 'nullable|string|max:255',
            'start_lat'     => 'nullable|numeric',
            'start_lng'     => 'nullable|numeric',
            'start_time'    => 'nullable|date_format:"H:i"',
            'end_time'      => 'nullable|date_format:"H:i"',
            'teams'         => ['required', 'array', function ($attribute, $value, $fail) {
                if ($value) {
                    if (count($value) == 0) {
                        $fail("The teams is empty");
                    }
                    foreach ($value as $id) {
                        if (Auth::user()->teams()->where('id', $id)->count() == 0) {
                            $fail("The team id {$id} is invalid");
                        }
                    }
                }
            }]
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $driver = $this->driverRepository->create($attributes);

        return $driver;
    }

    /**
     * @param array $attributes
     * @return Driver
     */
    public function update($id, array $attributes): Driver
    {
        $validator = Validator::make($attributes, [
            'name'          => 'nullable|string|max:100',
            'phone'         => 'nullable|string|max:40',
            'start_address' => 'nullable|string|max:255',
            'start_lat'     => 'nullable|numeric',
            'start_lng'     => 'nullable|numeric',
            'start_time'    => 'nullable|date_format:"H:i"',
            'end_time'      => 'nullable|date_format:"H:i"',
            'teams'         => ['nullable', 'array', function ($attribute, $value, $fail) {
                if ($value) {
                    if (count($value) == 0) {
                        $fail("The teams is empty");
                    }
                    foreach ($value as $id) {
                        if (Auth::user()->teams()->where('id', $id)->count() == 0) {
                            $fail("The team id {$id} is invalid");
                        }
                    }
                }
            }]
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $driver = $this->driverRepository->update($id, $attributes);

        return $driver;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function delete($id): void
    {
        $this->driverRepository->delete($id);
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function startStop(array $attributes): array
    {
        $validator = Validator::make($attributes, [
            'project_hash' => 'required|string',
            'driver_hash'  => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $project_driver = $this->driverRepository->getProjectDriver($attributes['project_hash'], $attributes['driver_hash']);

        if ($project_driver->status == ProjectDriver::STATUS_COMPLETED) {
            throw new CustomException('this project has already been completed.', 400);
        }

        $stop = $this->getNextStopWaiting($project_driver->routes);

        if (!$stop) {
            throw new CustomException('No stops are waiting', 400);
        }
        
        $this->stopRepository->start($stop, $project_driver, date('Y-m-d H:i:s'));

        return $this->serializeProjectDriver($project_driver);
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function arriveStop(array $attributes): array
    {
        $validator = Validator::make($attributes, [
            'project_hash'  => 'required|string',
            'driver_hash'   => 'required|string',
            'bags'          => 'nullable|numeric',
            'note'          => 'nullable|string',
            'image'         => 'required|file'
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $project_driver = $this->driverRepository->getProjectDriver($attributes['project_hash'], $attributes['driver_hash']);
    
        if ($project_driver->status == ProjectDriver::STATUS_WAITING) {
            throw new CustomException('this project has not started.', 400);
        }

        if ($project_driver->status == ProjectDriver::STATUS_COMPLETED) {
            throw new CustomException('this project has already been completed.', 400);
        }

        $stop = $this->getNextStopStarted($project_driver->routes);

        if (!$stop) {
            throw new CustomException('No stops are started', 400);
        }

        $this->stopRepository->arrive($stop, $project_driver, [
            'arrived_at'    => date('Y-m-d H:i:s'),
            'bags'          => isset($attributes['bags']) ? $attributes['bags'] : null,
            'note'          => isset($attributes['note']) ? $attributes['note'] : null,
            'image'         => $attributes['image']
        ]);

        return $this->serializeProjectDriver($project_driver, $stop);
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function skipStop(array $attributes): array
    {
        $validator = Validator::make($attributes, [
            'project_hash'  => 'required|string',
            'driver_hash'   => 'required|string',
            'note'          => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $project_driver = $this->driverRepository->getProjectDriver($attributes['project_hash'], $attributes['driver_hash']);
    
        if ($project_driver->status == ProjectDriver::STATUS_WAITING) {
            throw new CustomException('this project has not started.', 400);
        }

        if ($project_driver->status == ProjectDriver::STATUS_COMPLETED) {
            throw new CustomException('this project has already been completed.', 400);
        }

        $stop = $this->getNextStopStarted($project_driver->routes);

        if (!$stop) {
            throw new CustomException('No stops are started', 400);
        }

        $this->stopRepository->skip($stop, $project_driver, [
            'skipped_at'    => date('Y-m-d H:i:s'),
            'note'          => $attributes['note']
        ]);

        return $this->serializeProjectDriver($project_driver);
    }

    /**
     * @param int $stop_id
     * @param array $attributes
     * @return array
     */
    public function changeStopStatus(int $stop_id, array $attributes): array
    {
        $validator = Validator::make($attributes, [
            'project_hash'  => 'required|string',
            'driver_hash'   => 'required|string',
            'bags'          => 'nullable|numeric',
            'note'          => 'nullable|string',
            'image'         => 'required|file'
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->messages()->first(), 400);
        }

        $project_driver = $this->driverRepository->getProjectDriver($attributes['project_hash'], $attributes['driver_hash']);

        $index = array_search($stop_id, $project_driver->stops_order);

        if ($index === false) {
            throw new CustomException('Stop id not found.', 400);
        }

        if ($project_driver->routes[$index]['status'] != ProjectDriver::STATUS_SKIPPED) {
            throw new CustomException('This stop was not skipped.', 400);
        }

        $stop = Stop::find($stop_id);

        $this->stopRepository->changeStatus($stop, $project_driver, [
            'arrived_at'    => date('Y-m-d H:i:s'),
            'bags'          => isset($attributes['bags']) ? $attributes['bags'] : null,
            'note'          => isset($attributes['note']) ? $attributes['note'] : null,
            'image'         => $attributes['image']
        ]);

        return $this->serializeProjectDriver($project_driver, $stop);
    }

    /**
     * @param ProjectDriver $project_driver
     * @return array
     */
    private function serializeProjectDriver(ProjectDriver $project_driver, $stop = null): array
    {
        return [
            'hash'            => md5($project_driver->project_id),
            'name'            => $project_driver->project->name,
            'created_at'      => $project_driver->project->created_at,
            'routes'          => $project_driver->routes,
            'polyline_points' => $project_driver->polyline_points,
            'status'          => $project_driver->status,
            'driver'          => [
                'name'          => $project_driver->driver->name,
                'phone'         => ( isset( $stop->phone    ) ) ? $stop->phone    : '',
                'order_id'      => ( isset( $stop->order_id ) ) ? $stop->order_id : '',
                'start_address' => $project_driver->start_address,
                'start_lat'     => $project_driver->start_lat,
                'start_lng'     => $project_driver->start_lng,
                'start_time'    => $project_driver->driver->start_time
            ]
        ];
    }

    /**
     * @param array $routes
     * @return Stop
     */
    private function getNextStopWaiting(array $routes): ?Stop
    {
        foreach ($routes as $route) {
            if ($route['status'] == ProjectDriver::STATUS_WAITING) {
                return Stop::find($route['end_id']);
            }
        }

        return null;
    }

    /**
     * @param array $routes
     * @return Stop
     */
    private function getNextStopStarted(array $routes): ?Stop
    {
        foreach ($routes as $route) {
            if ($route['status'] == ProjectDriver::STATUS_STARTED) {
                return Stop::find($route['end_id']);
            }
        }

        return null;
    }
}