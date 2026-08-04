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
        string $message = 'Ocorreu um erro.',
        int $code = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
