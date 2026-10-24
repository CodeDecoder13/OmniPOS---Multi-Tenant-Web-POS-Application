<?php

namespace App\Http\Requests\Tenant;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeBusinessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_type' => ['required', new Enum(BusinessType::class)],
            'confirmation' => ['required', 'in:CHANGE'],
        ];
    }

    public function messages(): array
    {
        return ['confirmation.in' => 'Type CHANGE to confirm.'];
    }
}
