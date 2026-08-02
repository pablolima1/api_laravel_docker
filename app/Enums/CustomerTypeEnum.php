<?php

namespace App\Enums;

enum CustomerTypeEnum: string
{
    case Common = 'Common';
    case Shopkeeper = 'Shopkeeper';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Cliente Comum',
            self::Shopkeeper => 'Lojista',
        };
    }

    public function canSendMoney(): bool
    {
        return $this === self::Common;
    }
}