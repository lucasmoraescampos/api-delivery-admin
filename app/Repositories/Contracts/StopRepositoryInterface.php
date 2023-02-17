<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectDriver;
use App\Models\Stop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface StopRepositoryInterface
{
    /**
     * @param mixed $id
     * @return Stop
     */
    public function getById($id): Stop;

    /**
     * @param array $attributes
     * @return Stop
     */
    public function create(array $attributes): Stop;

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Stop
     */
    public function update($id, array $attributes): Stop;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function columnNames(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function import(array $attributes): Collection;

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param string $started_at
     * @return void
     */
    public function start(Stop &$stop, ProjectDriver &$project_driver, string $started_at): void;

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function arrive(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void;

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function skip(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void;

    /**
     * @param Stop $stop
     * @param ProjectDriver $projectDriver
     * @param array $attributes
     * @return void
     */
    public function changeStatus(Stop &$stop, ProjectDriver &$project_driver, array $attributes): void;
}