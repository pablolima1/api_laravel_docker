<?php

namespace App\Actions;

use App\Dto\TransferData;
use App\Enums\TransactionStatusEnum;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Authorization\ExternalAuthorizationService;
use Illuminate\Support\Facades\DB;

final class TransferMoneyAction
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly WalletRepository $walletRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly ExternalAuthorizationService $authorizationService,
    ) {}

    public function execute(TransferData $dto)
    {
        $payer = $this->customerRepository->findOrFail($dto->payer);
        $payee = $this->customerRepository->findOrFail($dto->payee);

        if (!$payer->canSendMoney()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value);
            throw new \Exception('Payer is not allowed to send money');
        }

        if ($this->walletRepository->balanceOf($payer->id) < $dto->value) {
            $this->recordFailure($payer->id, $payee->id, $dto->value);
            throw new \Exception('Insufficient balance');
        }

        if (! $this->authorizationService->authorize()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value);
            throw new \Exception('Transaction not authorized');
        }

        $transaction = DB::transaction(function () use ($payer, $payee, $dto) {
            $this->walletRepository->debit($payer->id, $dto->value);
            $this->walletRepository->credit($payee->id, $dto->value);

            return $this->transactionRepository->create(
                payerId: $payer->id,
                payeeId: $payee->id,
                amount: $dto->value,
                status: TransactionStatusEnum::Completed,
            );
        });

        SendTransferNotificationJob::dispatch($transaction);

        return $transaction;
    }

    private function recordFailure(int $payerId, int $payeeId, float $amount): void
    {
        $this->transactionRepository->create(
            payerId: $payerId,
            payeeId: $payeeId,
            amount: $amount,
            status: TransactionStatusEnum::Failed,
        );
    }
}
