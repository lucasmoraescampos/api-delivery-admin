<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class CustomersController extends Controller
{
    private $customersRepository;

    public function __construct(CustomerRepositoryInterface $customersRepository)
    {
        $this->customersRepository = $customersRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = $this->customersRepository->getByAuth();

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->customersRepository->create($request->all());

        $customers = $this->customersRepository->getByAuth();

        return response()->json([
            'success' => true,
            'message' => 'Successful registration',
            'data'    => $customers,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->customersRepository->update( $id, $request->all() );

        $customers = $this->customersRepository->getByAuth();

        return response()->json([
            'success' => true,
            'message' => 'Successful registration',
            'data'    => $customers,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
