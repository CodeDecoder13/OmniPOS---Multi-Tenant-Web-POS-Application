<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DiscountType;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Backward compat: wrap single payment fields into payments array
        if (! $this->has('payments') && $this->has('payment_method')) {
            $this->merge([
                'payments' => [
                    [
                        'method' => $this->input('payment_method'),
                        'amount' => $this->input('amount_tendered'),
                        'reference_number' => $this->input('reference_number'),
                    ],
                ],
            ]);
        }
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.variations' => ['nullable', 'array'],
            'items.*.variations.*.variation_group_name' => ['required_with:items.*.variations', 'string', 'max:255'],
            'items.*.variations.*.option_name' => ['required_with:items.*.variations', 'string', 'max:255'],
            'items.*.variations.*.price_modifier' => ['nullable', 'numeric', 'min:0'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*.addon_name' => ['required_with:items.*.addons', 'string', 'max:255'],
            'items.*.addons.*.addon_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenant->id)],
            // Split payments
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', new Enum(PaymentMethod::class)],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:255'],
            // Backward compat single payment fields (ignored if payments array is present)
            'payment_method' => ['nullable', new Enum(PaymentMethod::class)],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'required_with:discount_amount', new Enum(DiscountType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_type' => ['required', new Enum(OrderType::class)],
            'pos_operator_id' => ['nullable', 'integer'],
            'table_id' => ['nullable', 'integer', Rule::exists('tables', 'id')->where('tenant_id', $tenant->id)],
            'promotion_id' => ['nullable', 'integer', Rule::exists('promotions', 'id')->where('tenant_id', $tenant->id)],
            'order_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $discountType = $this->input('discount_type');
            $discountAmount = $this->input('discount_amount');

            if ($discountType === 'percentage' && $discountAmount > 100) {
                $validator->errors()->add('discount_amount', 'Percentage discount cannot exceed 100%.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one item to the cart.',
            'items.min' => 'Please add at least one item to the cart.',
            'payments.required' => 'At least one payment is required.',
            'discount_type.required_with' => 'Discount type is required when discount amount is provided.',
        ];
    }
}
