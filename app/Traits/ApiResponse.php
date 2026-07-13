<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Enveloppe de réponse unifiée conforme aux exigences de l'API v1.
     */
    protected function buildResponse(
        bool $success,
        string $message,
        mixed $data = [],
        mixed $errors = [],
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $message,
            'data' => $data,
            'meta' => array_merge(['version' => 'v1.0.0'], $meta),
            'errors' => $errors,
            'timestamp' => now()->timestamp,
            'requestId' => uniqid('req_v1_', true),
        ], $statusCode);
    }

    /**
     * Rendu simple de données brutes.
     */
    protected function renderData(array $data): JsonResponse
    {
        return $this->buildResponse(true, "Données récupérées avec succès.", $data);
    }
}
