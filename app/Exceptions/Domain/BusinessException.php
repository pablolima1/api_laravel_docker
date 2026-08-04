<?php

namespace App\Exceptions\Domain;

use Exception;

abstract class BusinessException extends Exception
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    public function context(): array
    {
        return [];
    }
}
