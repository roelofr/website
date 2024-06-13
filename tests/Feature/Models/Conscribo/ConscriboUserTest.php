<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Conscribo;

use App\Models\Conscribo\ConscriboUser;
use Tests\TestCase;

class ConscriboUserTest extends TestCase
{
    /**
     * Provides a list of name constructions that should result in a proper name.
     */
    public static function provideTestNameGenerationItems(): iterable
    {
        return [
            'empty' => ['', [
                'first_name' => null,
                'infix' => null,
                'last_name' => null,
            ]],
            'first and last' => ['Sam Jones', [
                'first_name' => 'Sam',
                'infix' => null,
                'last_name' => 'Jones',
            ], 'John Doe'],
            'first, infix and last' => ['Sam van der Kerk', [
                'first_name' => 'Sam',
                'infix' => 'van der',
                'last_name' => 'Kerk',
            ]],
            'infix and last only' => ['den Bakker', [
                'first_name' => null,
                'infix' => 'den',
                'last_name' => 'Bakker',
            ]],
            'ensure as-is' => ['SaM vAn Der BROEck', [
                'first_name' => 'SaM',
                'infix' => 'vAn Der',
                'last_name' => 'BROEck',
            ]],
        ];
    }

    /**
     * Provides a list of conscribo selector constructions that should result in a proper conscribo name.
     */
    public static function provideConscriboNameGenerationItems(): iterable
    {
        return [
            'empty' => ['', [
                'conscribo_id' => null,
                'conscribo_selector' => null,
            ]],
            'normal' => ['John Doe', [
                'conscribo_id' => 1,
                'conscribo_selector' => '1: John Doe',
            ]],
            'additional zeroes' => ['John Doe', [
                'conscribo_id' => 1,
                'conscribo_selector' => '001: John Doe',
            ]],
            'id mismatch' => ['2: John Doe', [
                'conscribo_id' => 1,
                'conscribo_selector' => '2: John Doe',
            ]],
        ];
    }

    /**
     * Test user name computation.
     * @dataProvider provideTestNameGenerationItems
     */
    public function test_name_generation(string $expectedName, array $attributes): void
    {
        $model = new ConscriboUser($attributes);

        $this->assertEquals($expectedName, $model->name);
    }

    /**
     * Tests conscribo "name" generation from selector.
     * @dataProvider provideConscriboNameGenerationItems
     */
    public function test_conscribo_name_generation(string $expectedName, array $attributes): void
    {
        $model = new ConscriboUser($attributes);

        $this->assertEquals($expectedName, $model->conscribo_name);
    }
}
