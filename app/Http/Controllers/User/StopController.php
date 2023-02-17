<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\StopRepositoryInterface;
use Illuminate\Http\Request;

class StopController extends Controller
{
    private $stopRepository;

    public function __construct(StopRepositoryInterface $stopRepository)
    {
        $this->stopRepository = $stopRepository;
    }

    public function store(Request $request)
    {
        $stop = $this->stopRepository->create($request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Successful registration',
            'data'      => $stop
        ]);
    }

    public function update(Request $request, $id)
    {
        $stop = $this->stopRepository->update($id, $request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Successful update',
            'data'      => $stop
        ]);
    }

    public function columnNames(Request $request)
    {
        $columnNames = $this->stopRepository->columnNames($request->all());

        return response()->json([
            'success'   => true,
            'data'      => $columnNames
        ]);
    }

    public function import(Request $request)
    {
        $stops = $this->stopRepository->import($request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Successful import',
            'data'      => $stops
        ]);
    }
}
