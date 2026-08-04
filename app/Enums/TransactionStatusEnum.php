<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case Completed = 'Completed';
    case Failed = 'Failed';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
        };
    }
}