<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['full', 'partial'])],
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required_if:type,partial', 'array', 'min:1'],
            'items.*.order_item_id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required_if' => 'Please select at least one item for partial refund.',
            'reason.required' => 'Please provide a reason for the refund.',
        ];
    }
}
