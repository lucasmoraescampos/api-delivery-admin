<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\DriverService;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    public function projects($driver_hash)
    {
        $projects = $this->driverService->getProjectsByDriver($driver_hash);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function project($driver_hash, $project_hash)
    {
        $project_driver = $this->driverService->getProjectDriver($project_hash, $driver_hash);

        return response()->json([
            'success' => true,
            'data' => $project_driver
        ]);
    }

    public function start(Request $request)
    {
        $project_driver = $this->driverService->startStop($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Started successfully',
            'data' => $project_driver
        ]);
    }

    public function arrive(Request $request)
    {
        $project_driver = $this->driverService->arriveStop($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stop arrived successfully',
            'data' => $project_driver
        ]);
    }

    public function skip(Request $request)
    {
        $project_driver = $this->driverService->skipStop($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stop skipped successfully',
            'data' => $project_driver
        ]);
    }

    public function changeStatus(Request $request, $stop_id)
    {
        $project_driver = $this->driverService->changeStopStatus($stop_id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stop updated successfully',
            'data' => $project_driver
        ]);
    }
}
