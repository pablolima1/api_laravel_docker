<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Domain\WalletNotFoundException;
use App\Models\Wallet;

final class WalletRepository
{
    /**
     * Retorna o saldo da carteira para o cliente informado.
     *
     * @throws WalletNotFoundException
     */
    public function balanceOf(int $customerId): float
    {
        return (float) $this->findWalletOrFail($customerId)->balance;
    }

    /**
     * Reduz o saldo da carteira para o cliente informado.
     *
     * @throws WalletNotFoundException
     */
    public function debit(int $customerId, float $amount): void
    {
        $this->findWalletOrFail($customerId)->decrement('balance', $amount);
    }

    /**
     * Aumenta o saldo da carteira para o cliente informado.
     *
     * @throws WalletNotFoundException
     */
    public function credit(int $customerId, float $amount): void
    {
        $this->findWalletOrFail($customerId)->increment('balance', $amount);
    }

    private function findWalletOrFail(int $customerId): Wallet
    {
        $wallet = Wallet::query()
            ->where('customer_id', $customerId)
            ->first();

        if ($wallet === null) {
            throw new WalletNotFoundException(walletId: $customerId);
        }

        return $wallet;
    }
}
