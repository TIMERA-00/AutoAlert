<?php

namespace Database\Factories;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $brands = [
            'Toyota' => ['RAV4', 'Corolla', 'Hilux', 'Land Cruiser', 'Yaris'],
            'Renault' => ['Clio', 'Captur', 'Duster', 'Megane', 'Kangoo'],
            'Peugeot' => ['208', '301', '3008', '508'],
            'Hyundai' => ['Tucson', 'i10', 'Accent', 'Creta'],
            'Mercedes' => ['Classe C', 'Classe A', 'GLC'],
            'BMW' => ['Serie 3', 'Serie 1', 'X3'],
            'Nissan' => ['Qashqai', 'Micra', 'Navara'],
            'Mitsubishi' => ['Lancer', 'ASX', 'Outlander'],
        ];

        $brand = $this->faker->randomElement(array_keys($brands));
        $model = $this->faker->randomElement($brands[$brand]);
        $year = $this->faker->numberBetween(2012, (int) date('Y'));
        $price = $this->faker->numberBetween(3_000_000, 45_000_000);

        return [
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'price' => $price,
            'mileage' => max(500, (int) ((int) date('Y') - $year) * $this->faker->numberBetween(8_000, 25_000)),
            'fuel' => $this->faker->randomElement(FuelType::cases()),
            'transmission' => $this->faker->randomElement(Transmission::cases()),
            'body_type' => $this->faker->randomElement(BodyType::cases()),
            'color' => $this->faker->randomElement(['Blanc', 'Noir', 'Argent', 'Gris', 'Bleu', 'Rouge']),
            'location' => $this->faker->randomElement(['Dakar', 'Thies', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Touba']),
            'description' => $this->faker->paragraph(),
            'status' => VehicleStatus::Published,
            'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'reference' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'view_count' => $this->faker->numberBetween(0, 250),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::Draft, 'published_at' => null]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::Published, 'published_at' => now()]);
    }

    public function sold(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::Sold]);
    }

    public function reserved(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::Reserved]);
    }

    public function brand(string $brand): static
    {
        return $this->state(fn () => ['brand' => $brand]);
    }

    public function forSale(int $maxPrice, ?int $minYear = null): static
    {
        return $this->state(fn () => array_filter([
            'price' => $this->faker->numberBetween(5_000_000, $maxPrice),
            'min_year' => null,
            'year' => $minYear ? $this->faker->numberBetween($minYear, (int) date('Y')) : null,
        ], fn ($v) => $v !== null));
    }

    public function withImages(int $count = 3): static
    {
        return $this->afterCreating(function (Vehicle $vehicle) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $vehicle->images()->create([
                    'url' => 'https://images.example.com/vehicles/'.Str::slug($vehicle->brand.'-'.$vehicle->model).'-'.($i + 1).'.jpg',
                    'provider' => 'external',
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                    'alt' => $vehicle->brand.' '.$vehicle->model,
                ]);
            }
        });
    }
}
