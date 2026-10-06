<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('page'));
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('page_sections', 'id')->where('page_id', $this->route('page')->id)],
        ];
    }
}
