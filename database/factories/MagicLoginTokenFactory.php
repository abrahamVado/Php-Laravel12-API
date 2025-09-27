<?php

namespace Database\Factories;

use App\Models\MagicLoginToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MagicLoginToken>
 */
class MagicLoginTokenFactory extends Factory
{
    protected $model = MagicLoginToken::class;

    public function definition(): array
    {
        $plain = Str::random(64);

        return [
            'id' => (string) Str::ulid(),
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
            'remember' => $this->faker->boolean(),
            'redirect_to' => $this->faker->boolean(30) ? $this->faker->url() : null,
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function used(): static
    {
        return $this->state(function () {
            return [
                'used_at' => now(),
                'used_ip' => $this->faker->ipv4(),
                'used_ua' => $this->faker->userAgent(),
            ];
        });
    }
}
