<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadApiStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required_without_all:email,telegram', 'string', 'max:255', 'regex:/^7\d{10}$/'],
            'email' => ['required_without_all:phone,telegram', 'email', 'max:255'],
            'telegram' => ['required_without_all:phone,email', 'string', 'max:255', 'regex:/^@\w+$/'],
            'comment' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'amount' => 'amount',
            'contact_name' => 'contact name',
            'phone' => 'phone',
            'email' => 'email',
            'telegram' => 'telegram',
            'comment' => 'comment',
        ];
    }
}
