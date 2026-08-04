<?php

namespace App\Actions;

use App\Dto\TransferData;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransferLogEvent;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Authorization\ExternalAuthorizationService;
use App\Support\Logging\TransferLogContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::channel('transfers')->info(TransferLogEvent::Started->value, TransferLogContext::fromData($dto));

        $payer = $this->customerRepository->findOrFail($dto->payer);
        $payee = $this->customerRepository->findOrFail($dto->payee);

        if (!$payer->canSendMoney()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEvent::MerchantCannotSend);
            throw new \Exception('Payer is not allowed to send money');
        }

        if ($this->walletRepository->balanceOf($payer->id) < $dto->value) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEvent::InsufficientBalance);
            throw new \Exception('Insufficient balance');
        }

        if (! $this->authorizationService->authorize()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEvent::NotAuthorized);
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

        Log::channel('transfers')->info(TransferLogEvent::Completed->value, TransferLogContext::fromTransaction($transaction));

        SendTransferNotificationJob::dispatch($transaction);

        return $transaction;
    }

    private function recordFailure(int $payerId, int $payeeId, float $amount, TransferLogEvent $event): void
    {
        Log::channel('transfers')->warning($event->value, ['payer_id' => $payerId, 'payee_id' => $payeeId, 'amount' => $amount]);

        $this->transactionRepository->create(
            payerId: $payerId,
            payeeId: $payeeId,
            amount: $amount,
            status: TransactionStatusEnum::Failed,
        );
    }
}
