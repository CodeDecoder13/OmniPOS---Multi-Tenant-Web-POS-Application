<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ShiftScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'user_id' => ['required', 'integer', Rule::exists('tenant_users', 'user_id')->where('tenant_id', $tenant->id)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['required', 'string', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
