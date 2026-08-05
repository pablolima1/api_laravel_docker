<?php

namespace App\Docs\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransactionResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'uuid', type: 'string', example: 'uuid'),
        new OA\Property(property: 'value', type: 'number', format: 'float', example: 100.5),
        new OA\Property(property: 'status', type: 'string', example: 'Completed'),
        new OA\Property(
            property: 'payer',
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'payer-uuid'),
                new OA\Property(property: 'name', type: 'string', example: 'Payer Name'),
            ]
        ),
        new OA\Property(
            property: 'payee',
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'payee-uuid'),
                new OA\Property(property: 'name', type: 'string', example: 'Payee Name'),
            ]
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-04T12:00:00Z'),
    ]
)]
class TransactionResourceSchema {}
