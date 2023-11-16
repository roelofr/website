<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use Illuminate\Database\Eloquent\Factories\Factory;

class MailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = $this->faker;

        return [
            'label' => "seed-{$faker->unique()->word()}",
            'subject' => $faker->sentence(),
            'body' => <<<MARKDOWN
            Dear {subject},

            {$faker->sentences($faker->numberBetween(1, 10), true)}
            MARKDOWN,
        ];
    }
}
