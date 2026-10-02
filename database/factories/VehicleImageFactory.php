<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleImage>
 */
class VehicleImageFactory extends Factory
{
    protected $model = VehicleImage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'url' => 'https://images.example.com/'.fake()->uuid().'.jpg',
            'provider' => 'external',
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
