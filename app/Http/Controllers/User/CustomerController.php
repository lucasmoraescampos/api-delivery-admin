<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private $customerRepository;

    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function index()
    {
        $customers = $this->customerRepository->getAll();

        return response()->json([
            'success' => true,
            'data'    => $customers
        ]);
    }

    public function store(Request $request)
    {
        $customer = $this->customerRepository->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully registered',
            'data'    => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = $this->customerRepository->update($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully updated',
            'data'    => $customer
        ]);
    }

    public function delete($id)
    {
        $this->customerRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully deleted',
        ]);
    }
}
