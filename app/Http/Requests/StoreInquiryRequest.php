<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('company') instanceof Company) {
            abort_unless(Company::visible()->whereKey($this->route('company')->id)->exists(), 404);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => $this->routeIs('group.inquiry') ? ['required', 'integer'] : ['exclude'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'string', 'max:0'],
            'consent' => ['accepted'],
        ];
    }
}
