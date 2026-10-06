<?php

namespace App\Http\Requests;

use App\Actions\Cms\ContentRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        ContentRegistry::assertItemSection($this->route('section'));

        return Gate::allows('update', $this->route('section'));
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('section_items', 'id')->where('page_section_id', $this->route('section')->id)],
        ];
    }
}
