<?php

namespace App\Repositories;

use App\Models\Customer;

final class CustomerRepository
{
    public function findOrFail(int $id): Customer
    {
        return Customer::query()->findOrFail($id);
    }
}