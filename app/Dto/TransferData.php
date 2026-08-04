<?php

namespace App\Dto;

final readonly class TransferData
{
    public function __construct(
        public float $value,
        public int $payer,
        public int $payee,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            value: (float) $data['value'],
            payer: (int) $data['payer'],
            payee: (int) $data['payee'],
        );
    }
}
