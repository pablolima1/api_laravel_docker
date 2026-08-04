<?php

namespace App\Support\Logging;

use App\Dto\TransferData;
use App\Models\Transaction;

final class TransferLogContext
{
    public static function fromData(TransferData $data): array
    {
        return [
            'payer_id' => $data->payer,
            'payee_id' => $data->payee,
            'amount' => $data->value,
        ];
    }

    public static function fromTransaction(Transaction $transaction): array
    {
        return [
            'transaction_uuid' => $transaction->uuid,
            'payer_id' => $transaction->payer_id,
            'payee_id' => $transaction->payee_id,
            'amount' => $transaction->amount,
            'status' => $transaction->status->name,
        ];
    }
}
