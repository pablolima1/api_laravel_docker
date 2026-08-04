<?php

namespace App\Repositories;

use App\Models\Wallet;

final class WalletRepository
{
    public function balanceOf(int $customerId): float
    {
        return (float) Wallet::query()
            ->where('customer_id', $customerId)
            ->value('balance');
    }

    public function debit(int $customerId, float $amount): void
    {
        Wallet::query()
            ->where('customer_id', $customerId)
            ->decrement('balance', $amount);
    }

    public function credit(int $customerId, float $amount): void
    {
        Wallet::query()
            ->where('customer_id', $customerId)
            ->increment('balance', $amount);
    }
}