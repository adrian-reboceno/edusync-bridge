<?php

declare(strict_types=1);

namespace Auth\Role\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SwitchRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'uuid'],
        ];
    }
}
