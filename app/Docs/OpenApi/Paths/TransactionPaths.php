<?php

namespace App\Docs\OpenApi\Paths;

use OpenApi\Attributes as OA;

class TransactionPaths
{
    #[OA\Post(
        path: '/api/v1/transfer',
        tags: ['Transactions'],
        summary: 'Realiza uma transferência entre clientes',
        description: 'Realiza a transferência de `value` de um cliente `payer` para `payee`.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TransferRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transferência realizada com sucesso',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Transferência realizada com sucesso.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TransactionResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function transfer(): void {}
}
