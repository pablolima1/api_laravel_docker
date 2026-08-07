<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class InsufficientBalanceException extends BusinessException
{
    public function __construct(
        private readonly float $currentBalance,
        private readonly float $requestedAmount,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: __('transaction.insufficient_balance'),
            previous: $previous,
        );
    }

    public function status(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function errorCode(): string
    {
        return 'INSUFFICIENT_BALANCE';
    }

    public function context(): array
    {
        return [
            'current_balance' => $this->currentBalance,
            'requested_amount' => $this->requestedAmount,
        ];
    }
}
