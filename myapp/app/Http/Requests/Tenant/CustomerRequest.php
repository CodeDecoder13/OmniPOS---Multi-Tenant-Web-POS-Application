<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');
        $customerId = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('customers')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($customerId),
            ],
            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($customerId),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
