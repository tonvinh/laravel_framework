<?php

namespace App\Base\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        $payload = match (true) {
            $data instanceof ResourceCollection => $data->response()->getData(assoc: true),
            $data instanceof JsonResource       => $data->response()->getData(assoc: true),
            default                             => ['data' => $data],
        };

        return response()->json($payload, $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return $this->success($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
