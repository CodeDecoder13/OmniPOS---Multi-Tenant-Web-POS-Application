<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50', 'regex:/^[+]?[\d\s\-().]+$/'],
            'staff_member_id' => ['required', 'integer', Rule::exists('staff_members', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
            // Local shop date and time; the service converts them to UTC.
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'product_ids' => ['required', 'array', 'min:1', 'max:20'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
