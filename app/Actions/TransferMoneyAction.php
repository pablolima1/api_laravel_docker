<?php

namespace App\Actions;

use App\Dto\TransferData;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransferLogEventEnum;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\PayerNotAllowedException;
use App\Exceptions\Domain\TransactionNotAuthorizedException;
use App\Helpers\LogTransactionContext;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Authorization\ExternalAuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class TransferMoneyAction
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository,
        private ExternalAuthorizationService $authorizationService,
    ) {}

    /**
     * Transfere dinheiro entre duas carteiras após validar as regras de negócio.
     *
     * @throws PayerNotAllowedException
     * @throws InsufficientBalanceException
     * @throws TransactionNotAuthorizedException
     */
    public function execute(TransferData $dto): Transaction
    {
        Log::channel('transfers')->info(TransferLogEventEnum::Started->value, LogTransactionContext::fromData($dto));

        $payer = $this->customerRepository->findOrFail($dto->payer);
        $payee = $this->customerRepository->findOrFail($dto->payee);

        if (!$payer->canSendMoney()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEventEnum::MerchantCannotSend);
            throw new PayerNotAllowedException();
        }

        $currentBalance = $this->walletRepository->balanceOf($payer->id);

        if ($currentBalance < $dto->value) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEventEnum::InsufficientBalance);
            throw new InsufficientBalanceException(
                currentBalance: $currentBalance,
                requestedAmount: $dto->value,
            );
        }

        if (! $this->authorizationService->authorize()) {
            $this->recordFailure($payer->id, $payee->id, $dto->value, TransferLogEventEnum::NotAuthorized);
            throw new TransactionNotAuthorizedException();
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

        Log::channel('transfers')->info(TransferLogEventEnum::Completed->value, LogTransactionContext::fromTransaction($transaction));

        SendTransferNotificationJob::dispatch($transaction);

        return $transaction;
    }

    /**
     * Persiste uma transação falhada e registra a entrada de log correspondente.
     */
    private function recordFailure(int $payerId, int $payeeId, float $amount, TransferLogEventEnum $event): void
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
