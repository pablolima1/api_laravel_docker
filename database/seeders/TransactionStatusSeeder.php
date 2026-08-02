<?php

namespace Database\Seeders;

use App\Enums\TransactionStatusEnum;
use App\Models\TransactionStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TransactionStatus::query()->upsert(
            [
                [
                    'name' => TransactionStatusEnum::Completed->value,
                ],
                [
                    'name' => TransactionStatusEnum::Failed->value,
                ],
            ],
            uniqueBy: ['name'],
            update: ['description'],
        );
    }
}
