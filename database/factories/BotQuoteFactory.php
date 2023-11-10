<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Models\BotQuoteType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotQuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->optional(0.4)->passthrough(User::inRandomOrder()->first(['id'])->id),
            'display_name' => $this->faker->name,
            'quote' => $this->faker->sentence,
            'quote_type' => $this->faker->randomElement([BotQuoteType::QUOTE, BotQuoteType::FACT]),
        ];
    }

    public function withRandomDate(): self
    {
        return $this->state(fn () => [
            'created_at' => $createdAt = $this->faker->dateTimeBetween('-5 months', '+4 weeks'),
            'updated_at' => $createdAt,
        ]);
    }

    public function sent(): self
    {
        return $this->state([
            'submitted_at' => $this->faker->dateTimeBetween('-5 months', 'now'),
        ]);
    }
}
