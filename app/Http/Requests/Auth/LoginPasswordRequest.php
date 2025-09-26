<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        return [
            'email' => ['required','email'],
            'password' => ['required','string'],
            'device_name' => ['sometimes','string','max:120'],
        ];
    }
}
