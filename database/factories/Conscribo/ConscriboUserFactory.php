<?php

declare(strict_types=1);

namespace Database\Factories\Conscribo;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conscribo\ConscriboUser>
 */
class ConscriboUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = $this->faker->unique()->numberBetween(0, 1_000);

        return [
            'conscribo_id' => $id,
            'conscribo_selector' => "{$id}: {$this->faker->name}",

            'first_name' => $this->faker->firstName(),
            'infix' => $this->faker->optional()->word(),
            'last_name' => $this->faker->lastName(),

            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
