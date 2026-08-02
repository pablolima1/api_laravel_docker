<?php

namespace App\Actions;

use App\Dto\TransferData;
use App\Enums\TransactionStatusEnum;
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

    public function execute(TransferData $data)
    {
        $payer = $this->customerRepository->findOrFail($data->payer);
        $payee = $this->customerRepository->findOrFail($data->payee);
        
        if (!$payer->canSendMoney()) {
            Throw new \Exception('Payer is not allowed to send money');
        }

        if ($this->walletRepository->balanceOf($payer->id) < $data->value) {
            Throw new \Exception('Insufficient balance');
        }

        if (! $this->authorizationService->authorize()) {
            Throw new \Exception('Transaction not authorized');
        }

        $transaction = DB::transaction(function () use ($payer, $payee, $data) {
            $this->walletRepository->debit($payer->id, $data->value);
            $this->walletRepository->credit($payee->id, $data->value);

            return $this->transactionRepository->create(
                payerId: $payer->id,
                payeeId: $payee->id,
                amount: $data->value,
                status: TransactionStatusEnum::Completed,
            );
        });
        
        return $transaction;
    }
}