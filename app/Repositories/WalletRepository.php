<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Domain\WalletNotFoundException;
use App\Models\Wallet;
use Illuminate\Support\Collection;

class WalletRepository
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
     * Reduz o saldo da carteira informada.
     */
    public function debit(Wallet $wallet, float $amount): void
    {
        $wallet->decrement('balance', $amount);
    }

    /**
     * Aumenta o saldo da carteira informada.
     */
    public function credit(Wallet $wallet, float $amount): void
    {
        $wallet->increment('balance', $amount);
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

    /**
     * Busca e trava (SELECT ... FOR UPDATE) as wallets dos clientes informados.
     * Deve ser chamado dentro de uma DB::transaction() já aberta — fora dela,
     * o lock é liberado imediatamente após a query, sem efeito real.
     *
     * @param  int[]  $customerIds
     * @return Collection<int, Wallet> indexada por customer_id
     */
    public function lockManyForUpdate(array $customerIds): Collection
    {
        $sortedIds = collect($customerIds)->unique()->sort()->values()->all();

        return Wallet::query()
            ->whereIn('customer_id', $sortedIds)
            ->orderBy('customer_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('customer_id');
    }
}
