<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LeadUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'status_id' => ['required', 'exists:lead_statuses,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Название',
            'description' => 'Описание',
            'amount' => 'Сумма',
            'contact_id' => 'Контакт',
            'status_id' => 'Статус',
        ];
    }
}
