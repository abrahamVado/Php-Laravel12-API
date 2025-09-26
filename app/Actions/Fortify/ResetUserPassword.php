<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, mixed>  $input
     */
    public function reset(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'password' => $this->passwordRules([
                'name'  => $user->name ?? null,
                'email' => $user->email ?? null,
            ]),
        ])->validate();

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password'            => Hash::make($validated['password']),
                'remember_token'      => Str::random(60),   // invalidate "remember me" sessions
                'password_changed_at' => now(),             // add this column if you want it
            ])->save();

            // If using Sanctum, revoke existing API tokens.
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            event(new PasswordReset($user));
        });
    }
}
