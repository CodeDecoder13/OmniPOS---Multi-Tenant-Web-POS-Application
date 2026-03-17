<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');
        $roleId = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
