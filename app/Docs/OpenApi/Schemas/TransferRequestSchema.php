<?php

namespace App\Docs\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransferRequest',
    type: 'object',
    required: ['value', 'payer', 'payee'],
    properties: [
        new OA\Property(property: 'value', type: 'number', format: 'float', example: 100.5),
        new OA\Property(property: 'payer', type: 'integer', example: 1),
        new OA\Property(property: 'payee', type: 'integer', example: 2),
    ]
)]
class TransferRequestSchema {}
