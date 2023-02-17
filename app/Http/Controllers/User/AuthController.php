<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(Request $request)
    {
        $user = $this->userRepository->authenticate($request->all());

        $token = $this->userRepository->createAccessToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Successfully authentication',
            'data'    => $user,
            'token'   => $token
        ]);
    }

    public function logout(Request $request)
    {
        $this->userRepository->invalidAccessToken($request->all());

        return response()->json([
            'success' => true,
            'message' => 'successfully logout'
        ]);
    }

    public function resume()
    {
        $resume = $this->userRepository->resume();

        return response()->json([
            'success' => true,
            'data' => $resume
        ]);
    }
}
