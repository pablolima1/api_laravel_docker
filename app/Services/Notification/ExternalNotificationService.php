<?php

namespace App\Services\Notification;

use App\Models\Transaction;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class ExternalNotificationService
{
    public function notify(Transaction $transaction): Response
    {
        return Http::timeout((int) config('services.notifier.timeout'))
            ->post(config('services.notifier.url'), [
                'transaction_uuid' => $transaction->uuid,
                'customer_id' => $transaction->payee_id,
                'amount' => $transaction->amount,
            ]);
    }
}