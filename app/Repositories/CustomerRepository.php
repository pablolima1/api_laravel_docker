<?php

namespace App\Repositories;

use App\Exceptions\Domain\UserNotFoundException;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class CustomerRepository
{
    public function findOrFail(int $id): Customer
    {
        try {
            return Customer::query()->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            throw new UserNotFoundException(userId: $id, previous: $exception);
        }
    }
}
