<?php

namespace App\Repositories;

use App\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use Illuminate\Support\Str;

final class TransactionRepository
{
    public function create(int $payerId, int $payeeId, float $amount, TransactionStatusEnum $status): Transaction
    {
        $statusId = TransactionStatus::query()
            ->where('name', $status->value)
            ->value('id');

        return Transaction::query()->create([
            'uuid' => Str::uuid(),
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount' => $amount,
            'transaction_status_id' => $statusId,
        ]);
    }
}