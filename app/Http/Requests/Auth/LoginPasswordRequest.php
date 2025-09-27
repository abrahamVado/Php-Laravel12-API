<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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

    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge([
                'email' => Str::of($email)->trim()->lower()->toString(),
            ]);
        }
    }
}
