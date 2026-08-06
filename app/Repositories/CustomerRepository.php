<?php

namespace App\Repositories;

use App\Exceptions\Domain\UserNotFoundException;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerRepository
{
    /**
     * Retrieves a customer or fails with a domain exception when not found.
     *
     * @throws UserNotFoundException
     */
    public function findOrFail(int $id): Customer
    {
        try {
            return Customer::query()->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            throw new UserNotFoundException(userId: $id, previous: $exception);
        }
    }
}
