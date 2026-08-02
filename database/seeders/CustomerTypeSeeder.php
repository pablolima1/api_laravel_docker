<?php

namespace Database\Seeders;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerType::query()->upsert(
            [
                [
                    'name' => CustomerTypeEnum::Common->value,
                ],
                [
                    'name' => CustomerTypeEnum::Shopkeeper->value,
                ],
            ],
            uniqueBy: ['name'],
            update: ['description'],
        );
    }
}
