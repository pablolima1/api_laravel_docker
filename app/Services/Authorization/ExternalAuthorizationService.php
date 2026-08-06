<?php

namespace App\Services\Authorization;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalAuthorizationService
{
    /**
     * Consulta autorização externa e retorna se foi concedida.
     */
    public function authorize(): bool
    {
        try {
            $response = Http::timeout((int) config('services.authorizer.timeout'))
                ->retry(2, 100)
                ->get(config('services.authorizer.url'));

            if ($response->failed()) {
                Log::channel('transfers')->warning('Serviço autorizador respondeu com erro.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return (bool) data_get($response->json(), 'data.authorization', false);
        } catch (Throwable $exception) {
            Log::channel('transfers')->error('Falha ao consultar o serviço autorizador.', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
