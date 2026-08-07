<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Formata uma resposta JSON de API bem-sucedida.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Operação realizada com sucesso.',
        int $code = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }


    /**
     * Formata uma resposta JSON de API de erro com detalhes opcionais.
     */
    protected function error(
        string $code,
        string $message,
        int $httpStatus = Response::HTTP_BAD_REQUEST,
        array $details = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $httpStatus);
    }
}
