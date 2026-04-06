<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('initial_stock', 'required|integer|min:0', function ($input) {
            return $this->isMethod('POST') && !$input->is_food;
        });
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');
        $productId = $this->route('product');

        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('tenant_id', $tenant->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('products')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($productId),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'lte:price'],
            'is_active' => ['sometimes', 'boolean'],
            'is_food' => ['sometimes', 'boolean'],
            'initial_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'variation_groups' => ['nullable', 'array'],
            'variation_groups.*.name' => ['required', 'string', 'max:255'],
            'variation_groups.*.is_required' => ['sometimes', 'boolean'],
            'variation_groups.*.options' => ['required', 'array', 'min:1'],
            'variation_groups.*.options.*.name' => ['required', 'string', 'max:255'],
            'variation_groups.*.options.*.price_modifier' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:addons,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ];
    }
}
