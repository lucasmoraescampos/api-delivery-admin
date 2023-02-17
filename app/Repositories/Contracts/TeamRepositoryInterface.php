<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use App\Models\Team;

interface TeamRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * @param int $id
     * @return Team
     */
    public function getById(int $id): Team;

    /**
     * @param array $attributes
     * @return Team
     */
    public function create(array $attributes): Team;

    /**
     * @param int $id
     * @param array $attributes
     * @return Team
     */
    public function update(int $id, array $attributes): Team;

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;

    /**
     * @param int $team_id
     * @param array $attributes
     * @return Team
     */
    public function attachManager(int $team_id, array $attributes): Team;

    /**
     * @param int $team_id
     * @param int $manager_id
     * @return Team
     */
    public function detachManager(int $team_id, int $manager_id): Team;
}
