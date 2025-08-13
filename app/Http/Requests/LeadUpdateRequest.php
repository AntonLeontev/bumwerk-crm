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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'contact_id' => ['sometimes', 'required', 'exists:contacts,id'],
            'status_id' => ['sometimes', 'required', 'exists:lead_statuses,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
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
            'user_id' => 'Ответственный',
        ];
    }
}
