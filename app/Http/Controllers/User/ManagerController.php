<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $users = $this->userRepository->getManagers();

        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->userRepository->createManager($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully registered',
            'data'    => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = $this->userRepository->updateManager($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully updated',
            'data'    => $customer
        ]);
    }

    public function delete($id)
    {
        $this->userRepository->deleteManager($id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully deleted',
        ]);
    }

    public function attachTeam(Request $request, $manager_id)
    {
        $user = $this->userRepository->attachTeam($manager_id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully linked',
            'data'    => $user
        ]);
    }

    public function detachTeam($manager_id, $team_id)
    {
        $user = $this->userRepository->detachTeam($manager_id, $team_id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully unlinked',
            'data'    => $user
        ]);
    }
}
