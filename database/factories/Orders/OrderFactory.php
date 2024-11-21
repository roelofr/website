<?php

declare(strict_types=1);

namespace Database\Factories\Orders;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Orders\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
        ];
    }

    /**
     * Define the model's default state with a null amount and fee.
     */
    public function withoutAmount(): self
    {
        return $this->state(fn () => [
            'amount' => null,
            'fee' => null,
            'total_amount' => null,
        ]);
    }

    /**
     * Define the model's default state with a random amount and fee.
     */
    public function withAmount(): self
    {
        return $this->state(function () {
            $amount = $this->faker->numberBetween(1_50, 35_00);
            $fee = $this->faker->numberBetween(0o15, max($amount * 0.05, 0o30));

            return [
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $amount + $fee,
            ];
        });
    }
}
