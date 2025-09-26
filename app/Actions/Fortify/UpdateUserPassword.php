<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

final class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $guard = config('fortify.guard', 'web');

        $validated = Validator::make(
            $input,
            [
                'current_password' => ['required', 'string', "current_password:{$guard}"],
                'password' => $this->passwordRules([
                    'name'  => $user->name ?? null,
                    'email' => $user->email ?? null,
                ]),
            ],
            [
                'current_password.current_password' => __('The provided password does not match your current password.'),
            ]
        )->validateWithBag('updatePassword');

        // Invalidate other device sessions (uses the current guard)
        Auth::shouldUse($guard);
        // Must be called BEFORE changing the stored password hash.
        Auth::logoutOtherDevices($validated['current_password']);

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password'            => Hash::make($validated['password']),
                'remember_token'      => Str::random(60), // bust "remember me" cookies
                'password_changed_at' => now(),           // add nullable column if you want this
            ])->save();

            // If using Sanctum for API tokens, revoke existing tokens.
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            // Optional: fire a domain event if you have one.
            if (class_exists(\App\Events\UserPasswordUpdated::class)) {
                event(new \App\Events\UserPasswordUpdated($user));
            }
        });
    }
}
