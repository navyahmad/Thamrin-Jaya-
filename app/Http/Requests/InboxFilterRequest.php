<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InboxFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Inquiry::class);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', Rule::in(['new', 'in_progress', 'resolved'])],
            'read' => ['nullable', Rule::in(['read', 'unread'])],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
