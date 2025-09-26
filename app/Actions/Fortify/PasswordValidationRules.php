<?php

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * Pass optional user context (name/email) to add similarity checks:
     *   $this->passwordRules(['name' => $name, 'email' => $email])
     *
     * @param  array{name?: string, email?: string}|null  $context
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(?array $context = null): array
    {
        $rule = Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();

        // Only call HaveIBeenPwned in non-local envs to avoid dev flakiness.
        if (! app()->isLocal()) {
            // Uncompromised with a small threshold (3) to avoid false positives.
            $rule = $rule->uncompromised(3);
        }

        $similarityCheck = $this->similarityClosure($context);

        return [
            'required',
            'string',
            'max:255',                 // guard absurdly long inputs
            'regex:/^\\S+$/',          // disallow spaces/tabs/newlines
            $rule,
            $similarityCheck,          // no-op if context not provided
            'confirmed',               // expects password_confirmation
        ];
    }

    /**
     * Returns a Closure validation rule that rejects passwords containing
     * obvious pieces of the user's name or email (case-insensitive).
     *
     * @param  array{name?: string, email?: string}|null $context
     * @return \Closure
     */
    protected function similarityClosure(?array $context): Closure
    {
        $needles = [];

        if (! empty($context['name'])) {
            // split name into words of length >= 3
            foreach (preg_split('/\\s+/', (string) $context['name']) as $part) {
                $part = Str::of($part)->ascii()->lower()->trim();
                if ($part->length() >= 3) {
                    $needles[] = (string) $part;
                }
            }
        }

        if (! empty($context['email'])) {
            $email = Str::of((string) $context['email'])->lower();
            $local = Str::of($email->before('@'))->ascii()->lower();
            // split by common separators in the local part
            foreach (preg_split('/[._+\\-]+/', (string) $local) as $part) {
                $part = Str::of($part)->trim();
                if ($part->length() >= 3) {
                    $needles[] = (string) $part;
                }
            }
        }

        // Deduplicate
        $needles = array_values(array_unique($needles));

        return function (string $attribute, mixed $value, Closure $fail) use ($needles): void {
            if (empty($needles) || ! is_string($value)) {
                return;
            }

            $hay = Str::of($value)->ascii()->lower();

            foreach ($needles as $n) {
                if ($n !== '' && Str::of($hay)->contains($n)) {
                    $fail(__('Your :attribute must not contain parts of your name or email.', ['attribute' => $attribute]));
                    return;
                }
            }
        };
    }
}
