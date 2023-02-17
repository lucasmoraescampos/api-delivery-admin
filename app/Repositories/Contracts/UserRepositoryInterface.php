<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getManagers(): Collection;

    /**
     * @param array $attributes
     * @return User
     */
    public function createManager(array $attributes): User;

    /**
     * @param int $id
     * @param array $attributes
     * @return User
     */
    public function updateManager(int $id, array $attributes): User;

    /**
     * @param int $id
     * @return void
     */
    public function deleteManager(int $id): void;

    /**
     * @param int $manager_id
     * @param array $attributes
     * @return User
     */
    public function attachTeam(int $manager_id, array $attributes): User;

    /**
     * @param int $manager_id
     * @param int $team_id
     * @return User
     */
    public function detachTeam(int $manager_id, int $team_id): User;

    /**
     * @param array $attributes
     * @return User
     */
    public function authenticate(array $attributes): User;

    /**
     * @param User $user
     * @return string
     */
    public function createAccessToken(User $user): string;

    /**
     * @param array $attributes
     * @return void
     */
    public function invalidAccessToken(array $attributes): void;

    /**
     * @return array
     */
    public function resume(): array;
}
