<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ContactUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'patronymic' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255', 'size:11'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! empty($this->get('phone'))) {
            $this->merge([
                'phone' => preg_replace('/\D/u', '', $this->get('phone')),
            ]);
        }
    }

    public function passedValidation(): void
    {
        if (
            is_null($this->input('name')) &&
            is_null($this->input('surname')) &&
            is_null($this->input('patronymic')) &&
            is_null($this->input('phone')) &&
            is_null($this->input('email')) &&
            is_null($this->input('telegram'))
        ) {
            throw ValidationException::withMessages([
                'name' => 'Хотя бы одно поле должно быть заполнено.',
                'surname' => 'Хотя бы одно поле должно быть заполнено.',
                'patronymic' => 'Хотя бы одно поле должно быть заполнено.',
                'phone' => 'Хотя бы одно поле должно быть заполнено.',
                'email' => 'Хотя бы одно поле должно быть заполнено.',
                'telegram' => 'Хотя бы одно поле должно быть заполнено.',
            ]);
        }
    }
}
