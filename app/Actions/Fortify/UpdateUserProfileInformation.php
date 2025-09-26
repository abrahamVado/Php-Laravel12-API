<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

final class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                // Prefer stricter validation if you’ve installed egulias/email-validator:
                // 'email:strict,dns,spoof'
                'email:filter',
                'max:255',
                Rule::unique(User::class)
                    ->ignore($user->id)
                    // If your users table uses SoftDeletes:
                    ->where(fn ($q) => method_exists($user, 'trashed') ? $q->whereNull('deleted_at') : $q),
            ],
        ])->validateWithBag('updateProfileInformation');

        // Normalize inputs
        $newName  = Str::of((string) $validated['name'])->trim()->squish()->toString();
        $newEmail = Str::of((string) $validated['email'])->trim()->lower()->toString();

        $nameChanged  = $newName !== $user->name;
        $emailChanged = $newEmail !== $user->email;

        if (! $nameChanged && ! $emailChanged) {
            return; // nothing to do
        }

        DB::transaction(function () use ($user, $newName, $newEmail, $emailChanged): void {
            if ($emailChanged && $user instanceof MustVerifyEmail) {
                $this->updateVerifiedUser($user, [
                    'name'  => $newName,
                    'email' => $newEmail,
                ]);
                return;
            }

            $user->forceFill([
                'name'  => $newName,
                'email' => $newEmail,
            ])->save();
        });
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name'              => $input['name'],
            'email'             => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
