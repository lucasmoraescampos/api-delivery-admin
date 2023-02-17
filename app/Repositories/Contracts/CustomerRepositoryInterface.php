<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use App\Models\Customer;

interface CustomerRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * @param int $id
     * @return Customer
     */
    public function getById(int $id): Customer;

    /**
     * @param array $attributes
     * @return Customer
     */
    public function create(array $attributes): Customer;

    /**
     * @param int $id
     * @param array $attributes
     * @return Customer
     */
    public function update(int $id, array $attributes): Customer;

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;

    /**
     * @param array $attributes
     * @return Customer
     */
    public function authenticate(array $attributes): Customer;

    /**
     * @param Customer $customer
     * @return string
     */
    public function createAccessToken(Customer $customer): string;

    /**
     * @param array $attributes
     * @return void
     */
    public function invalidAccessToken(array $attributes): void;
}
