<?php

namespace App\Docs\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Laravel Docker',
    description: 'API REST de transferências entre clientes, sem autenticação.'
)]
class OpenApiInfo {}
