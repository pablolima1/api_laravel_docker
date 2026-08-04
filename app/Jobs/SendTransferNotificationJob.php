<?php

namespace App\Jobs;

use App\Enums\NotificationLogEventEnum;
use App\Models\Notification;
use App\Models\Transaction;
use App\Services\Notification\ExternalNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SendTransferNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    public function handle(ExternalNotificationService $notificationService): void
    {
        $notification = Notification::query()->firstOrCreate(
            [
                'transaction_id' => $this->transaction->id,
                'customer_id' => $this->transaction->payee_id,
            ],
            [
                'uuid' => Str::uuid(),
                'channel' => 'email',
                'status' => 'pending',
                'attempts' => 0,
            ]
        );

        $notification->increment('attempts');

        try {
            $response = $notificationService->notify($this->transaction);

            if ($response->failed()) {
                throw new RuntimeException("Notificador respondeu com status {$response->status()}");
            }

            $notification->update([
                'status' => 'sent',
                'response_payload' => $response->json(),
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $notification->update([
                'response_payload' => ['error' => $exception->getMessage()],
            ]);

            Log::channel('transfers')->warning(NotificationLogEventEnum::SendFailed->value, [
                'transaction_id' => $this->transaction->id,
                'attempt' => $notification->attempts,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Notification::query()
            ->where('transaction_id', $this->transaction->id)
            ->where('customer_id', $this->transaction->payee_id)
            ->update(['status' => 'failed']);

        Log::channel('transfers')->error(NotificationLogEventEnum::RetriesExhausted->value, [
            'transaction_id' => $this->transaction->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
