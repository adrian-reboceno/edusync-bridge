<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query params para GET /api/v1/analytics/classes
 */
final class GetClassesListRequest extends FormRequest
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
        ];
    }
}
