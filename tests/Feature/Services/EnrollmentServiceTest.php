<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\EnrollmentServiceContract;
use App\Exceptions\EnrollmentFailedException;
use App\Facades\Enroll;
use App\Helpers\Str;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\States\Enrollment as States;
use App\Models\States\Enrollment\State;
use App\Models\Ticket;
use App\Models\User;
use App\Services\EnrollmentService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use LogicException;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    protected EnrollmentService $service;

    /**
     * Now is 2023-06-10T00:00:00Z.
     */
    public static function provideEnrollmentTimeframes(): array
    {
        return [
            'no dates' => [[], [], true],
            'activity enrollment starts in the future' => [
                ['enrollment_start' => '2023-06-11T00:00:00Z'],
                [],
                false,
            ],
            'activity enrollments end in the past' => [
                ['enrollment_end' => '2023-06-09T00:00:00Z'],
                [],
                false,
            ],
            'ticket available in the future' => [
                [],
                ['available_from' => '2023-06-11T00:00:00Z'],
                false,
            ],
            'ticket available in the past' => [
                [],
                ['available_from' => '2023-06-09T00:00:00Z'],
                true,
            ],
            'ticket available in the future, enrollment started' => [
                ['enrollment_start' => '2023-06-09T00:00:00Z'],
                ['available_from' => '2023-06-11T00:00:00Z'],
                false,
            ],
            'ticket available in the past, enrollment not yet started' => [
                ['enrollment_start' => '2023-06-11T00:00:00Z'],
                ['available_from' => '2023-06-09T00:00:00Z'],
                false,
            ],
        ];
    }

    /**
     * Returns a list of "private buy rights" and user factories.
     * @return array{0: bool, 1: null|callable}
     */
    public static function provideEnrollmentVisibilityScopes(): array
    {
        return [
            'guest' => [false, fn () => User::factory()->create()],
            'member' => [true, fn () => User::factory()->withRole('member')->create()],
        ];
    }

    /**
     * @before
     */
    public function bindEnrollmentService(): void
    {
        $this->afterApplicationCreated(fn () => $this->service = app(EnrollmentServiceContract::class));
    }

    public function test_get_enrollment(): void
    {
        $user = User::factory()->create();

        $activity = Activity::factory()->hasTickets(1)->create();
        $ticket = $activity->tickets->first();

        $this->assertNull($this->service->getEnrollment($user, $activity));

        // Make dummy enrollment
        $enrollment = Enrollment::factory()->for($user)->for($ticket)->create();

        // Should now return
        $this->assertNotNull($this->service->getEnrollment($user, $activity));
        $this->assertEquals($enrollment->id, $this->service->getEnrollment($user, $activity)->id);

        // Should also return when seeded, confirmed, and paid
        $enrollment->state->transitionTo(States\Seeded::class);
        $enrollment->save();

        $this->assertEquals($enrollment->id, $this->service->getEnrollment($user, $activity)?->id);

        $enrollment->state->transitionTo(States\Confirmed::class);
        $enrollment->save();

        $this->assertEquals($enrollment->id, $this->service->getEnrollment($user, $activity)?->id);

        $enrollment->state->transitionTo(States\Paid::class);
        $enrollment->save();

        $this->assertEquals($enrollment->id, $this->service->getEnrollment($user, $activity)?->id);

        // Cancel it, it should not return
        $enrollment->state->transitionTo(States\Cancelled::class);
        $enrollment->save();

        $this->assertNull($this->service->getEnrollment($user, $activity));
    }

    /**
     * @dataProvider provideEnrollmentTimeframes
     */
    public function test_enrollment_timeframes_are_properly_handled(array $activityProps, array $ticketProps, bool $canEnroll): void
    {
        Date::setTestNow('2023-06-10T00:00:00Z');
        $user = User::factory()->create();

        $activity = Activity::factory()->create($activityProps);
        Ticket::factory()->for($activity)->create($ticketProps);

        // Factories don't update the activity, so we need to refresh its
        $activity = Activity::with('tickets')->find($activity->id);

        $this->assertEquals($canEnroll, $this->service->canEnroll($user, $activity));
    }

    /**
     * @dataProvider provideEnrollmentVisibilityScopes
     */
    public function test_enrollment_visibility_scoping(bool $buyPrivate, Closure $userClosure): void
    {
        $user = value($userClosure);

        $futureDate = Date::now()->addDay();
        $pastDate = Date::now()->subDay();

        $activity = Activity::factory()->hasTickets(2, ['available_from' => $futureDate])->create();
        [$publicTicket, $privateTicket] = $activity->tickets;

        $publicTicket->update(['is_public' => true]);
        $privateTicket->update(['is_public' => false]);

        // No tickets
        $this->assertEquals(false, $this->service->canEnroll($user, $activity), 'Assert nobody can buy tickets when none are on sale');

        // Add a private ticket
        $privateTicket->update(['available_from' => $pastDate]);
        $this->assertEquals($buyPrivate, $this->service->canEnroll($user, $activity), 'Assert members can buy private tickets when available');

        // Add a public ticket
        $publicTicket->update(['available_from' => $pastDate]);
        $this->assertEquals(true, $this->service->canEnroll($user, $activity), 'Assert anyone can buy public tickets when both are available');

        // Take private ticket out of sale
        $privateTicket->update(['available_from' => $futureDate]);
        $this->assertEquals(true, $this->service->canEnroll($user, $activity), 'Assert anyone can buy public tickets when only public tickets available');

        // Close the enrollment, which should terminate all ticket sales.
        $activity->update(['enrollment_start' => $futureDate]);

        $this->assertEquals(false, $this->service->canEnroll($user, $activity), 'Assert nobody can buy tickets before enrollment starts');
    }

    public function test_ticket_quanity(): void
    {
        $user = (User::factory()->create());

        $activity = Activity::factory()->hasTickets(1, ['quantity' => 2])->create();
        $ticket = $activity->tickets->first();

        // Should have two seats left
        $this->assertTrue($this->service->canEnroll($user, $activity));

        // Occupy both seats
        [$enrollment] = Enrollment::factory(2)->forUser()->for($ticket)->create(['state' => States\Confirmed::class]);

        // Should no longer have room
        $this->assertFalse($this->service->canEnroll($user, $activity));

        // Cancel one of the enrollments
        $enrollment->state->transitionTo(States\Cancelled::class);
        $enrollment->save();

        // Should have room again
        $this->assertTrue($this->service->canEnroll($user, $activity));

        // Set activity seat limit to 1
        $activity->update(['seats' => 1]);

        // Should be full again
        $this->assertFalse($this->service->canEnroll($user, $activity));
    }

    public function test_public_with_infinite_seats(): void
    {
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'is_public' => true,
        ]);

        $ticket = $activity->tickets()->create([
            'title' => 'Guest ticket',
            'is_public' => true,
        ]);

        // Guest
        $this->assertTrue($this->service->canEnroll($user, $activity));

        $ticketOptions = $this->service->findTicketsForActivity($user, $activity);
        $this->assertCount(1, $ticketOptions);
        $this->assertTrue($ticket->is($ticketOptions[0]));

        // Logged in user
        $user = ($this->getGuestUser());

        $this->assertTrue($this->service->canEnroll($user, $activity));

        $ticketOptions = $this->service->findTicketsForActivity($user, $activity);
        $this->assertCount(1, $ticketOptions);
        $this->assertTrue($ticket->is($ticketOptions[0]));

        // Logged in member
        $user = ($this->getMemberUser());

        $this->assertTrue($this->service->canEnroll($user, $activity));

        $ticketOptions = $this->service->findTicketsForActivity($user, $activity);
        $this->assertCount(1, $ticketOptions);
        $this->assertTrue($ticket->is($ticketOptions[0]));
    }

    public function test_public_with_no_public_tickets(): void
    {
        $activity = Activity::factory()->create([
            'is_public' => true,
        ]);

        $activity->tickets()->create([
            'title' => 'Member ticket',
            'is_public' => false,
        ]);

        $guestUser = $this->getGuestUser();
        $this->assertFalse($this->service->canEnroll($guestUser, $activity));

        $memberUser = $this->getMemberUser();
        $this->assertTrue($this->service->canEnroll($memberUser, $activity));
    }

    public function test_private_enrollment(): void
    {
        $activity = Activity::factory()->create([
            'is_public' => false,
        ]);

        $activity->tickets()->create([
            'title' => 'Member ticket',
            'is_public' => false,
        ]);

        Auth::logout();

        $guestUser = $this->getGuestUser();
        $memberUser = $this->getMemberUser();

        $this->assertFalse($this->service->canEnroll($guestUser, $activity), 'Failed asserting non-members cannot enroll');
        $this->assertTrue($this->service->canEnroll($memberUser, $activity), 'Failed asserting members can enroll');
    }

    public function test_activity_with_limited_seats(): void
    {
        $activity = Activity::factory()->withTickets()->create([
            'seats' => 1,
        ]);

        $user1 = User::factory()->create();
        $this->assertTrue($this->service->canEnroll($user1, $activity));

        $this->assertInstanceOf(Enrollment::class, $this->service->createEnrollment($user1, $activity, $activity->tickets()->first()));

        $this->assertCount(1, $user1->enrollments()->get());

        // Check user2 cannot enroll
        $user2 = User::factory()->create();
        $this->assertFalse($this->service->canEnroll($user2, $activity));

        $this->expectException(EnrollmentFailedException::class);
        $this->service->createEnrollment($user2, $activity, $activity->tickets()->first());
    }

    /**
     * Test signing up a second time somehow.
     */
    public function test_re_enrollment(): void
    {
        $activity = Activity::factory()->withTickets()->create();
        $ticket = $activity->tickets->first();

        $user = User::factory()->create();

        $this->assertTrue($this->service->canEnroll($user, $activity));
        $this->assertInstanceOf(Enrollment::class, $this->service->createEnrollment($user, $activity, $ticket));

        $this->assertCount(1, $user->enrollments()->get());

        $this->assertFalse($this->service->canEnroll($user, $activity));

        $this->expectException(EnrollmentFailedException::class);
        $this->service->createEnrollment($user, $activity, $ticket);
    }

    /**
     * Test transfering an enrollment to yourself.
     */
    public function test_transfer_to_self(): void
    {
        $activity = Activity::factory()->withTickets()->create();
        $ticket = $activity->tickets->first();

        $user = User::factory()->create();

        // make enrollment
        $enrollment = $this->service->createEnrollment($user, $activity, $ticket);

        // Should pass without token
        $this->assertTrue($this->service->canTransfer($user, $enrollment), 'Failed asserting the enrollment accepts transfers');

        // Add transfer code
        $enrollment->transfer_secret = Str::random(32);
        $enrollment->save();

        // Transfer to self
        $this->expectException(LogicException::class);
        $this->service->transferEnrollment($user, $enrollment, $user);
    }

    public function test_transfer_cancelled_and_trashed(): void
    {
        $activity = Activity::factory()->withTickets()->create();
        $ticket = $activity->tickets->first();

        $user = User::factory()->create();

        // Check the user can enroll
        $this->assertTrue($this->service->canEnroll($user, $activity), 'Failed asserting the user can enroll into the activity');

        // Enroll user
        $enrollment = $this->service->createEnrollment($user, $activity, $ticket);
        $this->assertInstanceOf(Enrollment::class, $enrollment, 'Failed asserting the enrollment was created');

        // Check the enrollment can transfer
        $this->assertTrue($this->service->canTransfer($user, $enrollment), 'Failed asserting the enrollment can be transferred');

        // Cancel enrollment
        $this->assertTrue($enrollment->state->canTransitionTo(States\Cancelled::class), 'Failed asserting the enrollment can be cancelled');
        $enrollment = $enrollment->state->transitionTo(States\Cancelled::class)->refresh();

        // Check the enrollment can't transfer anymore
        $this->assertTrue($enrollment->state instanceof States\Cancelled);
        $this->assertFalse($this->service->canTransfer($user, $enrollment), 'Failed asserting a cancelled enrollment cannot be transferred');

        // Re-enroll
        $this->assertTrue($this->service->canEnroll($user, $activity), 'Failed asserting the user can re-enroll into the activity');
        $enrollment2 = $this->service->createEnrollment($user, $activity, $ticket);
        $this->assertInstanceOf(Enrollment::class, $enrollment2, 'Failed asserting the enrollment was created');

        // Check the enrollment can transfer again
        $this->assertTrue($this->service->canTransfer($user, $enrollment2), 'Failed asserting the enrollment can be transferred');

        // Soft-delete the enrollment
        $enrollment2->delete();

        // Check the enrollment can't transfer anymore
        $this->assertFalse($this->service->canTransfer($user, $enrollment2), 'Failed asserting the enrollment cannot be transferred after being deleted');

        // Ensure we've been using two separate objects
        $this->assertFalse($enrollment->is($enrollment2), 'Failed asserting the two enrollments are different');
    }

    /**
     * Test transfering a proper enrollment.
     */
    public function test_regular_transfer(): void
    {
        $activity = Activity::factory()->withTickets()->create();
        $ticket = $activity->tickets->first();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // make enrollment
        $enrollment = $this->service->createEnrollment($user1, $activity, $ticket);

        // Should pass without token
        $this->assertTrue($this->service->canTransfer($user1, $enrollment), 'Failed asserting the enrollment accepts transfers');

        // Check counts
        $this->assertCount(1, $user1->enrollments()->get());
        $this->assertCount(0, $user2->enrollments()->get());

        // Add transfer code
        $enrollment->transfer_secret = Str::random(32);
        $enrollment->save();

        // Transfer to self
        $enrollment2 = $this->service->transferEnrollment($user1, $enrollment, $user2);
        $this->assertTrue($enrollment2->is($enrollment), 'Failed asserting the same enrollment was returned');

        // Refresh
        $enrollment->refresh();
        $this->assertTrue($enrollment->user->is($user2), 'Failed asserting the enrollment was transferred');

        // Check counts
        $this->assertCount(0, $user1->enrollments()->get());
        $this->assertCount(1, $user2->enrollments()->get());

        // Test enrollment remains transferrable
        $this->assertTrue($this->service->canTransfer($user1, $enrollment), 'Failed asserting the enrollment accepts transfers after a transfer');

        // Test it loses its transfer code
        $this->assertNull($enrollment->transfer_secret, 'Failed asserting the enrollment transfer code is wiped after handover');
    }

    public function test_transfer_unstable_enrollment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $activity = Activity::factory()->create();

        $enrollment = $user1->enrollments()->make([
            'state' => States\Seeded::class,
        ]);
        $enrollment->activity()->associate($activity);
        $enrollment->transfer_secret = Str::random(32);
        $enrollment->save();

        $this->assertTrue($this->service->canTransfer($user1, $enrollment), 'Failed asserting the enrollment can be transferred');
        $this->assertNotNull($enrollment->expire, 'Failed asserting that the enrollment was assigned expiry');

        $beforeExpiration = $enrollment->expire;

        Date::setTestNow(Date::now()->addMinutes(35));

        $this->service->transferEnrollment($user1, $enrollment, $user2);

        $enrollment->refresh();

        $this->assertGreaterThanOrEqual(
            $beforeExpiration,
            $enrollment->expire,
            'Failed asserting that the enrollment expiration was updated',
        );
    }

    public function test_transfer_stable_enrollment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $activity = Activity::factory()->create();

        $enrollment = $user1->enrollments()->make([
            'state' => States\Confirmed::class,
        ]);
        $enrollment->activity()->associate($activity);
        $enrollment->transfer_secret = Str::random(32);
        $enrollment->save();

        $this->assertTrue($user1->is($enrollment->user), 'Failed assserting user1 is the owner');

        $this->service->transferEnrollment($user1, $enrollment, $user2);

        $enrollment->refresh();

        $this->assertFalse($user1->is($enrollment->user), 'Failed assserting user1 is no longer the owner');
        $this->assertTrue($user2->is($enrollment->user), 'Failed asserting user2 is the owner');

        $this->assertNull($enrollment->transfer_secret, 'Failed asserting transfer secret is cleared');
    }

    /**
     * Check if enrollment states and consumption state have the proper impact on the
     * transferability of the enrollment.
     */
    public function test_transfer_ability(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->withTickets()->create();
        $ticket = $activity->tickets->first();

        $factory = Enrollment::factory()->for($activity)->for($ticket)->has(User::factory());
        $validEnrollment = $factory->create([
            'state' => States\Confirmed::class,
        ]);
        $createdEnrollment = $factory->create([
            'state' => States\Created::class,
        ]);
        $cancelledEnrollment = $factory->create([
            'state' => States\Cancelled::class,
        ]);
        $consumedEnrollment = $factory->create([
            'state' => States\Confirmed::class,
            'consumed_at' => Date::now(),
        ]);
        $trashedEnrollment = $factory->create([
            'state' => States\Confirmed::class,
        ]);
        $trashedEnrollment->delete();

        $this->assertTrue($this->service->canTransfer($user, $validEnrollment), 'Failed asserting a confirmed, clean enrollment can be transferred');
        $this->assertTrue($this->service->canTransfer($user, $createdEnrollment), 'Failed asserting a created enrollment cannot be transferred');
        $this->assertFalse($this->service->canTransfer($user, $cancelledEnrollment), 'Failed asserting a cancelled enrollment cannot be transferred');
        $this->assertFalse($this->service->canTransfer($user, $consumedEnrollment), 'Failed asserting a consumed enrollment cannot be transferred');
        $this->assertFalse($this->service->canTransfer($user, $trashedEnrollment), 'Failed asserting a trashed enrollment cannot be transferred');

        $activity->start_date = Date::now()->subHour();
        $activity->end_date = Date::now()->addHour();
        $activity->save();

        $this->assertFalse($this->service->canTransfer($user, $validEnrollment->fresh()), 'Failed asserting a confirmed, cleanenrollment cannot be transferred after event start');
        $this->assertFalse($this->service->canTransfer($user, $createdEnrollment->fresh()), 'Failed asserting a created enrollment cannot be transferred after event start');
    }

    public function test_can_enroll_failure_cases(): void
    {
        $activity = Activity::factory()->withTickets()->create([
            'seats' => 1,
        ]);

        $user = User::factory()->create();
        $user2 = User::factory()->create();

        // Test ticket unavailability
        $ticket = $activity->tickets()->first();
        $ticket->available_from = Date::now()->addDays(1);
        $ticket->save();

        $this->assertFalse($this->service->canEnroll($activity, $user));

        // Test seat availability
        $ticket->available_from = null;
        $ticket->save();
    }

    public function test_permissions_on_started_and_ended_activities(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $activity = Activity::factory()->withTickets()->create([
            'start_date' => Date::now()->addDay(),
            'end_date' => Date::now()->addDay()->addHour(),
        ]);
        $ticket = $activity->tickets->first();

        // Create enrollment under user1
        $this->assertInstanceOf(Enrollment::class, $enrollment = $this->service->createEnrollment($user1, $activity, $ticket));

        // Created, switch to user2
        $this->assertTrue($this->service->canEnroll($user2, $activity), 'Failed asserting users can enroll before start');
        $this->assertTrue($this->service->canTransfer($user2, $enrollment), 'Failed asserting users can transfer before start');

        $activity->forceFill([
            'start_date' => Date::now()->subDay(),
            'end_date' => Date::now()->addDay(),
        ])->save();
        $enrollment->refresh();

        $this->assertTrue($this->service->canEnroll($user2, $activity), 'Failed asserting users can enroll after start');
        $this->assertFalse($this->service->canTransfer($user2, $enrollment), 'Failed asserting users cannot transfer after start');

        $activity->forceFill([
            'start_date' => Date::now()->subDay(),
            'end_date' => Date::now()->subDay(),
        ])->save();
        $enrollment->refresh();

        $this->assertFalse($this->service->canEnroll($user2, $activity), 'Failed asserting users cannot enroll after end');
        $this->assertFalse($this->service->canTransfer($user2, $enrollment), 'Failed asserting users cannot transfer after end');
    }
}
