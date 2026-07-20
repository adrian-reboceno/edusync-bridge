<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'client_type' => ['nullable', 'in:WEB,MOBILE'],
            'totp_code' => ['nullable', 'string', 'size:6'],
            'selected_role_id' => ['nullable', 'uuid'],
        ];
    }
}
