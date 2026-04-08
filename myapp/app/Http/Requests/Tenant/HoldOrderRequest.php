<?php

namespace App\Http\Requests\Tenant;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class HoldOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.variations' => ['nullable', 'array'],
            'items.*.variations.*.variation_group_name' => ['required_with:items.*.variations', 'string'],
            'items.*.variations.*.option_name' => ['required_with:items.*.variations', 'string'],
            'items.*.variations.*.price_modifier' => ['nullable', 'numeric'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*.addon_name' => ['required_with:items.*.addons', 'string'],
            'items.*.addons.*.addon_price' => ['nullable', 'numeric'],
            'customer_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_type' => ['nullable', new Enum(OrderType::class)],
            'table_id' => ['nullable', 'integer'],
        ];
    }
}
