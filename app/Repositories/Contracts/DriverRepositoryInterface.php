<?php

namespace App\Repositories\Contracts;

use App\Models\Driver;
use App\Models\ProjectDriver;
use Illuminate\Support\Collection;

interface DriverRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * @param int $team_id
     * @return Collection
     */
    public function getByTeam(int $team_id): Collection;

    /**
     * @param mixed $id
     * @return Driver
     */
    public function getById($id): Driver;

    /**
     * @param mixed $driver_hash
     * @return Collection
     */
    public function getProjectsByDriver($driver_hash): Collection;

    /**
     * @param string $project_hash
     * @param string $driver_hash
     * @return ProjectDriver
     */
    public function getProjectDriver(string $project_hash, string $driver_hash): ProjectDriver;

    /**
     * @param array $attributes
     * @return Driver
     */
    public function create(array $attributes): Driver;

    /**
     * @param array $attributes
     * @return Driver
     */
    public function update($id, array $attributes): Driver;

    /**
     * @param mixed $id
     * @return void
     */
    public function delete($id): void;
}