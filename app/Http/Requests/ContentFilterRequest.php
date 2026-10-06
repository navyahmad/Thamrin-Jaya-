<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ContentFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view', $this->route('company'));
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'category' => [$this->route('type') === 'products' ? 'nullable' : 'missing', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
