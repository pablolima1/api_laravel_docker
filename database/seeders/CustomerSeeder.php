<?php

namespace Database\Seeders;

use App\Enums\CustomerTypeEnum;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    private const int TOTAL_COMMON_CUSTOMERS = 10;
    private const int TOTAL_SHOPKEEPERS = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonTypeId = CustomerType::query()
            ->where('name', CustomerTypeEnum::Common->value)
            ->value('id');

        $shopkeeperTypeId = CustomerType::query()
            ->where('name', CustomerTypeEnum::Shopkeeper->value)
            ->value('id');

        DB::transaction(function () use ($commonTypeId, $shopkeeperTypeId) {
            $this->createCustomers(self::TOTAL_COMMON_CUSTOMERS, $commonTypeId, document: fn() => fake('pt_BR')->unique()->cpf(false));
            $this->createCustomers(self::TOTAL_SHOPKEEPERS, $shopkeeperTypeId, document: fn() => fake('pt_BR')->unique()->cnpj(false));
        });
    }

    private function createCustomers(int $amount, int $customerTypeId, \Closure $document): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $customer = Customer::create([
                'uuid' => Str::uuid(),
                'name' => fake('pt_BR')->name(),
                'document' => $document(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'customer_type_id' => $customerTypeId,
            ]);

            Wallet::create([
                'uuid' => Str::uuid(),
                'customer_id' => $customer->id,
                'balance' => fake()->randomFloat(2, 100, 5000),
            ]);
        }
    }
}
