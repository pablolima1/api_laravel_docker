<?php

use App\Enums\CustomerTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Wallet;
use Database\Seeders\CustomerTypeSeeder;
use Database\Seeders\TransactionStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function seedLookupTables(): void
{
    (new CustomerTypeSeeder())->run();
    (new TransactionStatusSeeder())->run();
}

function createCustomerWithWallet(CustomerTypeEnum $type, float $balance): Customer
{
    $typeId = CustomerType::query()->where('name', $type->value)->value('id');

    $customer = Customer::create([
        'uuid' => Str::uuid(),
        'name' => fake('pt_BR')->name(),
        'document' => $type === CustomerTypeEnum::Shopkeeper
            ? fake('pt_BR')->unique()->cnpj(false)
            : fake('pt_BR')->unique()->cpf(false),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'customer_type_id' => $typeId,
    ]);

    Wallet::create([
        'uuid' => Str::uuid(),
        'customer_id' => $customer->id,
        'balance' => $balance,
    ]);

    return $customer;
}

function fakeAuthorizer(bool $authorized = true): void
{
    Http::fake([
        config('services.authorizer.url') => Http::response([
            'data' => ['authorization' => $authorized],
        ]),
    ]);
}

beforeEach(function () {
    seedLookupTables();
    Queue::fake(); // isola o teste do endpoint da entrega real de notificação
});

it('realiza a transferência com sucesso no caminho feliz', function () {
    fakeAuthorizer(authorized: true);

    $payer = createCustomerWithWallet(CustomerTypeEnum::Common, balance: 500.00);
    $payee = createCustomerWithWallet(CustomerTypeEnum::Shopkeeper, balance: 0.00);

    $response = $this->postJson('/api/v1/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Transferência realizada com sucesso.',
        ])
        ->assertJsonStructure([
            'data' => ['uuid', 'value', 'status', 'payer', 'payee', 'created_at'],
        ]);

    expect($response->json('data.status'))->toBe(TransactionStatusEnum::Completed->value);

    $this->assertDatabaseHas('wallets', ['customer_id' => $payer->id, 'balance' => 400.00]);
    $this->assertDatabaseHas('wallets', ['customer_id' => $payee->id, 'balance' => 100.00]);
    $this->assertDatabaseHas('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
        'amount' => 100.00,
    ]);

    Queue::assertPushed(App\Jobs\SendTransferNotificationJob::class);
});

it('retorna 403 quando lojista tenta enviar dinheiro', function () {
    fakeAuthorizer(authorized: true);

    $payer = createCustomerWithWallet(CustomerTypeEnum::Shopkeeper, balance: 500.00);
    $payee = createCustomerWithWallet(CustomerTypeEnum::Common, balance: 0.00);

    $response = $this->postJson('/api/v1/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(403)->assertJson(['success' => false]);

    // Saldo não deve ter sido alterado — a regra bloqueou antes de qualquer débito.
    $this->assertDatabaseHas('wallets', ['customer_id' => $payer->id, 'balance' => 500.00]);
    $this->assertDatabaseHas('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
        'amount' => 100.00,
    ]);
});

it('retorna 422 quando o saldo é insuficiente', function () {
    fakeAuthorizer(authorized: true);

    $payer = createCustomerWithWallet(CustomerTypeEnum::Common, balance: 10.00);
    $payee = createCustomerWithWallet(CustomerTypeEnum::Shopkeeper, balance: 0.00);

    $response = $this->postJson('/api/v1/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
    $this->assertDatabaseHas('wallets', ['customer_id' => $payer->id, 'balance' => 10.00]);
});

it('retorna 422 quando o autorizador externo nega a transferência', function () {
    fakeAuthorizer(authorized: false);

    $payer = createCustomerWithWallet(CustomerTypeEnum::Common, balance: 500.00);
    $payee = createCustomerWithWallet(CustomerTypeEnum::Shopkeeper, balance: 0.00);

    $response = $this->postJson('/api/v1/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
    $this->assertDatabaseHas('wallets', ['customer_id' => $payer->id, 'balance' => 500.00]);
});

it('retorna 422 quando o payload é inválido', function () {
    $response = $this->postJson('/api/v1/transfer', [
        'value' => -50.00,
        'payer' => 999999, // não existe
        'payee' => 999999, // não existe
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value', 'payer', 'payee'], 'error.details');
});

it('retorna 422 quando payer e payee são o mesmo cliente', function () {
    $payer = createCustomerWithWallet(CustomerTypeEnum::Common, balance: 500.00);

    $response = $this->postJson('/api/v1/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payer->id,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['payee'], 'error.details');
});