<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PayerNotAllowedException extends BusinessException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            message: __('transaction.unauthorized_user'),
            previous: $previous,
        );
    }

    public function status(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    public function errorCode(): string
    {
        return 'PAYER_NOT_ALLOWED';
    }
}
