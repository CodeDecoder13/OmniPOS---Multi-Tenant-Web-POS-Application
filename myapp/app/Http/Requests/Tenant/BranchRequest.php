<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');
        $branchId = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'alpha_dash',
                'max:20',
                Rule::unique('branches')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($branchId),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
