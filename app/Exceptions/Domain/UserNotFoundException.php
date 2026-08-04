<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class UserNotFoundException extends BusinessException
{
    public function __construct(
        private readonly ?int $userId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: 'User not found.',
            previous: $previous,
        );
    }

    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function errorCode(): string
    {
        return 'USER_NOT_FOUND';
    }

    public function context(): array
    {
        return $this->userId === null ? [] : ['user_id' => $this->userId];
    }
}
