<?php

declare(strict_types=1);

namespace Database\Factories\Conscribo;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conscribo\ConscriboGroup>
 */
class ConscriboGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conscribo_id' => $this->faker->unique()->numberBetween(20_000, 21_000),
            'name' => $this->faker->words(3, true),
        ];
    }
}
