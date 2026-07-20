<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'email' => $this->resource['email'],
            'first_name' => $this->resource['first_name'],
            'last_name' => $this->resource['last_name'],
            'status' => $this->resource['status'],
            'two_factor_enabled' => $this->resource['two_factor_enabled'] ?? false,
            'must_change_password' => $this->resource['must_change_password'] ?? false,
        ];
    }
}
