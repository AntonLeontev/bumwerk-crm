<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LeadSaveNewOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'columns' => ['required', 'array'],
            'columns.*.status_id' => ['required', 'integer', 'exists:lead_statuses,id'],
            'columns.*.ids' => ['nullable', 'array'],
            'columns.*.ids.*' => ['integer', 'exists:leads,id'],
        ];
    }
}
