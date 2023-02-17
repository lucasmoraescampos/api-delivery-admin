<?php

namespace App\Repositories;

use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamRepository implements TeamRepositoryInterface
{
    protected $model;

    /**
     * TeamRepository constructor.
     *
     * @param Team $model
     */
    public function __construct(Team $model)
    {
        $this->model = $model;
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
    {
        $user = Auth::user();

        $teams = $user->teams()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($user->type == User::TYPE_ADMIN) {

            foreach ($teams as &$team) {

                $team->managers = TeamUser::where('team_id', $team->id)
                    ->leftJoin('users', 'users.id', 'teams_has_users.user_id')
                    ->where('users.type', User::TYPE_MANAGER)
                    ->get();

            }

        }

        return $teams;
    }

    /**
     * @param int $id
     * @return Team
     */
    public function getById(int $id): Team
    {
        $team = Auth::user()
            ->teams()
            ->where('id', $id)
            ->firstOrFail();

        $team->managers = TeamUser::where('team_id', $team->id)
            ->leftJoin('users', 'users.id', 'teams_has_users.user_id')
            ->where('users.type', User::TYPE_MANAGER)
            ->get();

        return $team;
    }

    /**
     * @param array $attributes
     * @return Team
     */
    public function create(array $attributes): Team
    {
        $this->validateCreate($attributes);

        $team = $this->model->create(['name' => $attributes['name']]);

        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => Auth::id()
        ]);

        return $this->getById($team->id);
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return Team
     */
    public function update(int $id, array $attributes): Team
    {
        $this->validateUpdate($attributes);

        $team = Auth::user()
            ->teams()
            ->where('id', $id)
            ->firstOrFail();

        $team->fill(['name' => $attributes['name']]);

        $team->save();

        return $this->getById($id);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        Auth::user()
            ->teams()
            ->where('id', $id)
            ->firstOrFail()
            ->delete();
    }

    /**
     * @param int $team_id
     * @param array $attributes
     * @return Team
     */
    public function attachManager(int $team_id, array $attributes): Team
    {
        $this->validateAttachManager($attributes);

        $team = $this->getById($team_id);

        $team->users()->attach($attributes['manager_id']);

        return $this->getById($team->id);
    }

    /**
     * @param int $team_id
     * @param int $manager_id
     * @return Team
     */
    public function detachManager(int $team_id, int $manager_id): Team
    {
        $team = $this->getById($team_id);

        $team->users()->detach($manager_id);

        return $this->getById($team_id);
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateCreate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name' => 'required|string|max:200'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateUpdate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name' => 'sometimes|string|max:200'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateAttachManager(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'manager_id'       => 'required|numeric|exists:users,id'
        ]);

        $validator->validate();
    }
}
