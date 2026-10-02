<?php

namespace Database\Factories;

use App\Enums\AlertFrequency;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Toyota RAV4', 'SUV automatique', 'Berline budget', 'Diesel moins de 15M']),
            'brand' => $this->faker->randomElement(['Toyota', 'Renault', 'Peugeot', 'Hyundai', null]),
            'model' => $this->faker->randomElement(['RAV4', 'Clio', '3008', 'Tucson', null]),
            'min_year' => $this->faker->numberBetween(2018, 2022),
            'max_price' => $this->faker->numberBetween(10_000_000, 35_000_000),
            'max_mileage' => $this->faker->numberBetween(50_000, 200_000),
            'frequency' => AlertFrequency::Immediate,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function daily(): static
    {
        return $this->state(fn () => ['frequency' => AlertFrequency::Daily]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => ['frequency' => AlertFrequency::Weekly]);
    }
}
