<?php

declare(strict_types=1);

namespace Database\Factories\Google;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Google\GoogleMailList>
 */
class GoogleMailListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email(),
            'aliases' => [
                $this->faker->email(),
            ],
        ];
    }
}
