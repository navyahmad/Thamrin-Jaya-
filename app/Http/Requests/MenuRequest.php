<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('menu'));
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'is_active' => ['sometimes', 'boolean'], 'key' => ['missing'], 'site_id' => ['missing']];
    }
}
