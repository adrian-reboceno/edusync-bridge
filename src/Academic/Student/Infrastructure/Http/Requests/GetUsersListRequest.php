<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query params para GET /api/v1/analytics/users
 */
final class GetUsersListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'role' => ['string', 'in:Student,Teacher,Administrator,Mentor,Monitor,TA'],
            'activated' => ['boolean'],
            'search' => ['string', 'max:100'],
            'order_by' => ['string', 'in:last_login_at,first_login_at,joined_at,first_name,last_name'],
            'order_dir' => ['string', 'in:asc,desc'],
        ];
    }
}
