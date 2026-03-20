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

    public function rules(): array
    {
        $tenant = $this->attributes->get('current_tenant');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenant->id)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenant->id)],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', new Enum(DiscountType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_type' => ['required', new Enum(OrderType::class)],
            'pos_operator_id' => ['nullable', 'integer'],
            'table_id' => ['nullable', 'integer', Rule::exists('tables', 'id')->where('tenant_id', $tenant->id)],
            'promotion_id' => ['nullable', 'integer', Rule::exists('promotions', 'id')->where('tenant_id', $tenant->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one item to the cart.',
            'items.min' => 'Please add at least one item to the cart.',
            'amount_tendered.min' => 'Amount tendered cannot be negative.',
        ];
    }
}
