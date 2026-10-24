<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'source_branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenant->id), 'different:destination_branch_id'],
            'destination_branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenant->id)],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
        ];
    }
}
