<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class ProjectController extends Controller
{
    private $projectRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository   = $projectRepository;
    }

    public function index()
    {
        $projects = $this->projectRepository->getByCustomer();

        return response()->json([
            'success'   => true,
            'data'      => $projects,
        ]);
    }

    public function show($id)
    {
        $project = $this->projectRepository->getByCustomerId($id);

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }
}