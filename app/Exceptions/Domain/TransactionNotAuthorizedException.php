<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class TransactionNotAuthorizedException extends BusinessException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            message: 'Transaction not authorized.',
            previous: $previous,
        );
    }

    public function status(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    public function errorCode(): string
    {
        return 'TRANSACTION_NOT_AUTHORIZED';
    }
}
