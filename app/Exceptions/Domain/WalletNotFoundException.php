<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class WalletNotFoundException extends BusinessException
{
    public function __construct(
        private readonly ?int $walletId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: __('transaction.wallet_not_found'),
            previous: $previous,
        );
    }

    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function errorCode(): string
    {
        return 'WALLET_NOT_FOUND';
    }

    public function context(): array
    {
        return $this->walletId === null ? [] : ['wallet_id' => $this->walletId];
    }
}
