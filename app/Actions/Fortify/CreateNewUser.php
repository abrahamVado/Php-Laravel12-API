<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        // Base rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                // Use stricter email validation if you have egulias/email-validator installed:
                // 'email:strict,dns,spoof'
                'email:filter',
                'max:255',
                // If your User model uses SoftDeletes, ignore soft-deleted rows:
                Rule::unique(User::class)->whereNull('deleted_at'),
            ],
            // Trait-provided password rules (includes confirmation if you add 'confirmed' there)
            'password' => $this->passwordRules(),
        ];

        // Require Terms only if Fortify's feature is enabled
        if (config('fortify.terms_and_privacy_policy')) {
            $rules['terms'] = ['accepted'];
        }

        $validated = Validator::make($input, $rules)->validate();

        // Normalize inputs
        $name  = Str::of((string) $validated['name'])->trim()->squish()->toString();
        $email = Str::of((string) $validated['email'])->trim()->lower()->toString();

        // Atomic create
        return DB::transaction(function () use ($name, $email, $validated): User {
            return User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($validated['password']),
            ]);
        });
    }
}
