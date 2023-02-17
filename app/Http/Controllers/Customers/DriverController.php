<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DriverRepositoryInterface;

class DriverController extends Controller
{
    private $driverRepository;

    public function __construct(DriverRepositoryInterface $driverRepository)
    {
        $this->driverRepository = $driverRepository;
    }

    public function index()
    {
        $drivers = $this->driverRepository->getAll();

        return response()->json([
            'success' => true,
            'data'    => $drivers,
        ]);
    }

}
