<?php

namespace App\Dto;

final readonly class TransferData
{
    public function __construct(
        public float $value,
        public int $payer,
        public int $payee,
    ) {}

    /**
     * Cria um DTO de transferência a partir de payload validado.
     *
     * @param array{value: float|int|string, payer: int|string, payee: int|string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: (float) $data['value'],
            payer: (int) $data['payer'],
            payee: (int) $data['payee'],
        );
    }
}
