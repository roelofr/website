<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    /**
     * Disable rate limit.
     * @before
     */
    public function disableRateLimit(): void
    {
        $this->afterApplicationCreated(fn () => RateLimiter::for('api', fn () => Limit::none()));
    }

    /**
     * Test the index route.
     */
    public function test_index(): void
    {
        $futureActivities = Activity::factory()->count(3)->create();
        $pastActivities = Activity::factory()->createMany([
            [
                'start_date' => Date::now()->subYear()->subHour(),
                'end_date' => Date::now()->subYear(),
            ],
            [
                'start_date' => Date::now()->subMonth()->subHour(),
                'end_date' => Date::now()->subMonth(),
            ],
            [
                'start_date' => Date::now()->subWeek()->subHour(),
                'end_date' => Date::now()->subWeek(),
            ],
        ]);

        $pastActivities = Activity::where('end_date', '<', Date::now())->get();

        $this->getJson('/api/activities')
            ->assertUnauthorized();

        dd($pastActivities->only(['id', 'start_date', 'end_date']));

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/activities')
            ->assertOk()
            ->assertJsonFragment([
                'data' => $futureActivities->only('id')->toArray(),
            ]);

        $this->getJson('/api/activities?past=1')
            ->assertOk()
            ->assertJsonFragment([
                'data' => $pastActivities->only('id')->toArray(),
            ]);
    }
}
