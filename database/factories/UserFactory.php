<?php

namespace Database\Factories;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();

        return [
            'first_name' => $first,
            'last_name' => $last,
            'email' => Str::lower($first.'.'.$last.$this->faker->numberBetween(1, 9999)).'@example.com',
            'phone' => '+221 7'.$this->faker->numerify('#######'),
            'email_verified_at' => now(),
            'password' => static::$password ??= bcrypt('Password@2024'),
            'role' => UserRole::User,
            'is_active' => true,
            'notify_email' => true,
            'notify_whatsapp' => false,
            'notify_in_app' => true,
            'default_frequency' => AlertFrequency::Immediate,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function whatsappEnabled(): static
    {
        return $this->state(fn () => ['notify_whatsapp' => true]);
    }

    public function digest(): static
    {
        return $this->state(fn () => ['default_frequency' => AlertFrequency::Daily]);
    }

    public function withAlerts(int $count = 2): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $user->alerts()->create([
                    'name' => 'Alerte '.($i + 1),
                    'brand' => $this->faker->randomElement(['Toyota', 'Renault', 'Peugeot', 'Hyundai']),
                    'max_price' => $this->faker->numberBetween(10_000_000, 30_000_000),
                    'min_year' => $this->faker->numberBetween(2018, 2023),
                    'fuel' => $this->faker->randomElement(FuelType::cases()),
                    'transmission' => $this->faker->randomElement(Transmission::cases()),
                    'body_type' => $this->faker->randomElement(BodyType::cases()),
                    'frequency' => AlertFrequency::Immediate,
                ]);
            }
        });
    }
}
