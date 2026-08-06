<?php

use App\Actions\TransferMoneyAction;
use App\Dto\TransferData;
use App\Enums\CustomerTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\PayerNotAllowedException;
use App\Exceptions\Domain\TransactionNotAuthorizedException;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\Wallet;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Authorization\ExternalAuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

function makeCustomer(int $id, CustomerTypeEnum $type): Customer
{
    $customer = new Customer();
    $customer->id = $id;
    $customer->setRelation('type', tap(new CustomerType(), fn ($t) => $t->name = $type->value));

    return $customer;
}

function makeWallet(int $customerId, float $balance): Wallet
{
    $wallet = new Wallet();
    $wallet->customer_id = $customerId;
    $wallet->balance = $balance;

    return $wallet;
}

beforeEach(function () {
    Log::shouldReceive('channel')->with('transfers')->andReturnSelf();
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();
    Queue::fake();

    $this->customerRepository = Mockery::mock(CustomerRepository::class);
    $this->walletRepository = Mockery::mock(WalletRepository::class);
    $this->transactionRepository = Mockery::mock(TransactionRepository::class);
    $this->authorizationService = Mockery::mock(ExternalAuthorizationService::class);

    $this->action = new TransferMoneyAction(
        $this->customerRepository,
        $this->walletRepository,
        $this->transactionRepository,
        $this->authorizationService,
    );
});

it('lança exceção quando lojista tenta enviar dinheiro', function () {
    $payer = makeCustomer(1, CustomerTypeEnum::Shopkeeper);
    $payee = makeCustomer(2, CustomerTypeEnum::Common);

    $this->customerRepository->shouldReceive('findOrFail')->with(1)->andReturn($payer);
    $this->customerRepository->shouldReceive('findOrFail')->with(2)->andReturn($payee);

    $this->transactionRepository->shouldReceive('create')
        ->once()
        ->with(1, 2, 100.0, TransactionStatusEnum::Failed)
        ->andReturn(new Transaction());

    expect(fn () => $this->action->execute(new TransferData(value: 100.0, payer: 1, payee: 2)))
        ->toThrow(PayerNotAllowedException::class);
});

it('lança exceção quando saldo é insuficiente', function () {
    $payer = makeCustomer(1, CustomerTypeEnum::Common);
    $payee = makeCustomer(2, CustomerTypeEnum::Common);

    $this->customerRepository->shouldReceive('findOrFail')->with(1)->andReturn($payer);
    $this->customerRepository->shouldReceive('findOrFail')->with(2)->andReturn($payee);
    $this->walletRepository->shouldReceive('balanceOf')->with(1)->andReturn(50.0);

    $this->transactionRepository->shouldReceive('create')
        ->once()
        ->with(1, 2, 100.0, TransactionStatusEnum::Failed)
        ->andReturn(new Transaction());

    expect(fn () => $this->action->execute(new TransferData(value: 100.0, payer: 1, payee: 2)))
        ->toThrow(InsufficientBalanceException::class);
});

it('lança exceção quando o autorizador externo nega', function () {
    $payer = makeCustomer(1, CustomerTypeEnum::Common);
    $payee = makeCustomer(2, CustomerTypeEnum::Common);

    $this->customerRepository->shouldReceive('findOrFail')->with(1)->andReturn($payer);
    $this->customerRepository->shouldReceive('findOrFail')->with(2)->andReturn($payee);
    $this->walletRepository->shouldReceive('balanceOf')->with(1)->andReturn(500.0);
    $this->authorizationService->shouldReceive('authorize')->once()->andReturn(false);

    $this->transactionRepository->shouldReceive('create')
        ->once()
        ->with(1, 2, 100.0, TransactionStatusEnum::Failed)
        ->andReturn(new Transaction());

    expect(fn () => $this->action->execute(new TransferData(value: 100.0, payer: 1, payee: 2)))
        ->toThrow(TransactionNotAuthorizedException::class);
});

it('completa a transferência com sucesso no caminho feliz', function () {
    $payer = makeCustomer(1, CustomerTypeEnum::Common);
    $payee = makeCustomer(2, CustomerTypeEnum::Common);

    $payerWallet = makeWallet(1, 500.0);
    $payeeWallet = makeWallet(2, 0.0);

    $expectedTransaction = new Transaction();
    $expectedTransaction->id = 1;
    $expectedTransaction->setRelation('status', tap(new TransactionStatus(), function ($status) {
        $status->name = TransactionStatusEnum::Completed->value;
    }));

    $this->customerRepository->shouldReceive('findOrFail')->with(1)->andReturn($payer);
    $this->customerRepository->shouldReceive('findOrFail')->with(2)->andReturn($payee);
    $this->walletRepository->shouldReceive('balanceOf')->with(1)->andReturn(500.0);
    $this->authorizationService->shouldReceive('authorize')->once()->andReturn(true);

    // DB::transaction é mockado pra só executar a closure, sem tocar em banco real.
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (Closure $callback) => $callback());

    $this->walletRepository->shouldReceive('lockManyForUpdate')
        ->once()
        ->with([1, 2])
        ->andReturn(collect([1 => $payerWallet, 2 => $payeeWallet]));

    $this->walletRepository->shouldReceive('debit')->once()->with($payerWallet, 100.0);
    $this->walletRepository->shouldReceive('credit')->once()->with($payeeWallet, 100.0);

    $this->transactionRepository->shouldReceive('create')
        ->once()
        ->with(1, 2, 100.0, TransactionStatusEnum::Completed)
        ->andReturn($expectedTransaction);

    $result = $this->action->execute(new TransferData(value: 100.0, payer: 1, payee: 2));

    expect($result)->toBe($expectedTransaction);
    Queue::assertPushed(App\Jobs\SendTransferNotificationJob::class);
});