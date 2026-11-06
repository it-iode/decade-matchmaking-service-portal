<?php

namespace Database\Factories;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\User\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Turn off the master email switch on the user's notification settings row.
     * (The row itself is created by the User "created" observer.)
     */
    public function unsubscribedFromEmails(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->notificationSetting()->update(['email_notifications_enabled' => false]);
        });
    }

    /**
     * Opt the user out of specific taxonomy values for an entity.
     *
     * @param  'opportunity'|'request'  $entity
     * @param  array<int, string>  $values
     */
    public function optedOutOf(string $entity, array $values): static
    {
        return $this->afterCreating(function (User $user) use ($entity, $values): void {
            $user->notificationSetting()->update([$entity => $values]);
        });
    }
}
