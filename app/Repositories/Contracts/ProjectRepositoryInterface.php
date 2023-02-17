<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /**
     * @param array $attributes
     * @return array
     */
    public function getAll(array $attributes): array;

    /**
     * @param mixed $id
     * @return Project
     */
    public function getById($id): Project;

    /**
     * @param array $attributes
     * @return Project
     */
    public function create(array $attributes): Project;

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function update($id, array $attributes): Project;

    /**
     * @param mixed $id
     * @return void
     */
    public function delete($id): void;

    /**
     * @param int $id
     * @param array $attributes
     * @return Project
     */
    public function clone(int $id, array $attributes): Project;

    /**
     * @param int $project_id
     * @param int $driver_id
     * @param array $attributes
     * @return Project
     */
    public function addProjectDriver(int $project_id, int $driver_id, array $attributes): Project;

    /**
     * @param int $project_id
     * @param int $driver_id
     * @param array $attributes
     * @return Project
     */
    public function updateProjectDriver(int $project_id, int $driver_id, array $attributes): Project;

    /**
     * @param int $project_id
     * @param int $driver_id
     * @return Project
     */
    public function deleteProjectDriver(int $project_id, int $driver_id): Project;

    /**
     * @param int $project_id
     * @param int $driver_id
     * @return Project
     */
    public function deleteProjectStop(int $project_id, int $stop_id): Project;

    /**
     * @param mixed $id
     * @return Project
     */
    public function optimize($id): Project;

    /**
     * @param mixed $id
     * @return Project
     */
    public function dispatch($id): Project;

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function reverseRoute($id, array $attributes): Project;

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function reorder($id, array $attributes): Project;

    /**
     * @param mixed $id
     * @param array $attributes
     * @return Project
     */
    public function swapRoute($id, array $attributes): Project;

    /**
     * @param mixed $project_id
     * @param mixed $driver_id
     * @return void
     */
    public function sendSMS($project_id, $driver_id) : void;

    /**
     * @param mixed $project_id
     * @return void
     */
    public function sendSMSAll($project_id): void;

    /**
     * @param int $id
     * @return Collection
     */
    public function summaryExport(int $id): Collection;

    /**
     * @param int $id
     * @return Collection
     */
    public function solutionExport(int $id): Collection;

    /**
     * @param int $id
     * @return Collection
     */
    public function routeExport(int $id): Collection;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function report(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function reportDetails(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return float
     */
    public function reportPercent(array $attributes): float;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function bagsReport(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function bagsReportDetails(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return Collection
     */
    public function driversReport(array $attributes): Collection;


    /**
     * @param array $attributes
     * @return Collection
     */
    public function driversReportDetails(array $attributes): Collection;

    /**
     * @param array $attributes
     * @return array
     */
    public function deliveries(array $attributes): array;
}