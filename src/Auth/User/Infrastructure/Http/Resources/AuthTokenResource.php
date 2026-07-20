<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'],
            'refresh_token' => $this->resource['refresh_token'] ?? null,
            'token_type' => 'Bearer',
            'expires_in' => $this->resource['expires_in'] ?? 900,
            'active_role' => $this->resource['active_role'] ?? null,
            'permissions' => $this->resource['permissions'] ?? [],
        ];
    }
}
