<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $host = $this->faker->domainWord().'.example.com';

        return [
            'name' => ucfirst($this->faker->word()).' Auto',
            'base_url' => 'https://'.$host,
            'type' => $this->faker->randomElement(SourceType::cases()),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
