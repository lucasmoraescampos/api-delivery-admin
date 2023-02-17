<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    private $teamRepository;

    public function __construct(TeamRepositoryInterface $teamRepository)
    {
        $this->teamRepository = $teamRepository;
    }

    public function index()
    {
        $team = $this->teamRepository->getAll();

        return response()->json([
            'success'   => true,
            'data'      => $team
        ]);
    }

    public function store(Request $request)
    {
        $team = $this->teamRepository->create($request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Team registered successfully',
            'data'      => $team
        ]);
    }

    public function update(Request $request, $id)
    {
        $team = $this->teamRepository->update($id, $request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Team updated successfully',
            'data'      => $team
        ]);
    }

    public function delete($id)
    {
        $this->teamRepository->delete($id);

        return response()->json([
            'success'   => true,
            'message'   => 'Team deleted successfully'
        ]);
    }

    public function attachManager(Request $request, $team_id)
    {
        $team = $this->teamRepository->attachManager($team_id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Successfully linked',
            'data'    => $team
        ]);
    }

    public function detachManager($team_id, $manager_id)
    {
        $team = $this->teamRepository->detachManager($team_id, $manager_id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully unlinked',
            'data'    => $team
        ]);
    }
}
