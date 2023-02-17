<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $customersRepository;

    public function __construct(CustomerRepositoryInterface $customersRepository)
    {
        $this->customersRepository = $customersRepository;
    }

    public function login(Request $request)
    {
        $user  = $this->customersRepository->authenticate($request->all());
        $token = $user ? $this->customersRepository->createAccessToken($user) : null;

        return response()->json([
            'success' => true,
            'message' => 'Successful authentication',
            'data'    => $user,
            'token'   => $token
        ]);
    }

    public function logout(Request $request)
    {
        $this->customersRepository->invalidAccessToken($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sessão encerrada com sucesso'
        ]);
    }
}
