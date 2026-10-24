<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('tenant_id', $tenant->id)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenant->id)],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
