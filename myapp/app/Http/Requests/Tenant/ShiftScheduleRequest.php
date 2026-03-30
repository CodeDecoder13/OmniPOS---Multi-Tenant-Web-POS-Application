<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ShiftScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['required', 'string', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
