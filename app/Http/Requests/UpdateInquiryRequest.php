<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('inquiry'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required_without:read', Rule::in(['new', 'in_progress', 'resolved'])],
            'read' => ['required_without:status', Rule::in(['read', 'unread'])],
            'company_id' => ['missing'],
            'read_at' => ['missing'],
            'name' => ['missing'],
            'email' => ['missing'],
            'phone' => ['missing'],
            'subject' => ['missing'],
            'message' => ['missing'],
        ];
    }
}
