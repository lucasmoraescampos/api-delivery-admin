<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    private $driverRepository;

    public function __construct(DriverRepositoryInterface $driverRepository)
    {
        $this->driverRepository = $driverRepository;
    }

    public function index(Request $request)
    {
        $drivers = $this->driverRepository->getAll($request->all());

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    public function showTeam($team_id)
    {
        $drivers = $this->driverRepository->getByTeam($team_id);

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    public function store(Request $request)
    {
        $driver = $this->driverRepository->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successful registration',
            'data' => $driver
        ]);
    }

    public function update(Request $request, $id)
    {
        $driver = $this->driverRepository->update($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successful update',
            'data' => $driver
        ]);
    }

    public function delete($id)
    {
        $this->driverRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Successful deletion'
        ]);
    }
}
