<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptCustomizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'receipt_header' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'receipt_thank_you_message' => ['nullable', 'string', 'max:255'],
            'receipt_show_address' => ['boolean'],
            'receipt_show_phone' => ['boolean'],
            'receipt_show_customer' => ['boolean'],
            'receipt_show_table' => ['boolean'],
            'receipt_show_order_type' => ['boolean'],
            'receipt_show_tax_breakdown' => ['boolean'],
            'receipt_width' => ['nullable', 'string', 'in:58mm,80mm'],
        ];
    }
}
