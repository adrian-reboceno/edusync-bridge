<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query params para GET /api/v1/analytics/users/daily-access
 */
final class GetDailyAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['integer', 'min:1', 'max:365'],
            'user_id' => ['integer'],
        ];
    }
}
