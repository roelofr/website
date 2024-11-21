<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\Enroll;
use App\Models\Activity;
use App\Models\User;
use Tests\TestCase;

class EnrollTest extends TestCase
{
    /**
     * Ensure the Facade adds the user as first argument.
     */
    public function test_proper_auth_handling(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($user);

        Enroll::partialMock()
            ->expects('getEnrollment')
            ->once()
            ->withArgs([$user, $activity])
            ->andReturnNull();

        $this->assertNull(Enroll::getEnrollment($activity));
    }
}
