<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateConscriboUserJob;
use App\Models\Conscribo\ConscriboCommittee;
use App\Models\Conscribo\ConscriboGroup;
use App\Models\Conscribo\ConscriboUser;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UpdateConscriboUserJobTest extends TestCase
{
    public function test_roles_computation_empty(): void
    {
        /** @var ConscriboUser $user */
        $user = ConscriboUser::factory()->create();

        $roles = UpdateConscriboUserJob::getRolesForConscriboUser($user);

        $this->assertIsIterable($roles);
        $this->assertCount(0, $roles);
    }

    public function test_roles_computation_happy_trail(): void
    {
        /** @var ConscriboUser $user */
        $user = ConscriboUser::factory()->create();

        [$groupOne, $groupTwo] = ConscriboGroup::factory()->count(2)->create();
        [$comOne, $comTwo, $comThree] = ConscriboCommittee::factory()->count(3)->create();

        $memberRole = Role::findByName('member');
        $acRole = Role::findByName('ac');
        $dcRole = Role::findByName('dc');

        $groupOne->assignRole($memberRole);

        $comTwo->assignRole($acRole);
        $comThree->assignRole($dcRole);

        $user->groups()->attach([$groupOne->id, $groupTwo->id]);
        $user->committees()->attach([$comOne->id, $comTwo->id]);

        $roles = UpdateConscriboUserJob::getRolesForConscriboUser($user);

        $this->assertIsIterable($roles);
        $this->assertCount(2, $roles);

        $this->assertContains($memberRole->name, $roles);
        $this->assertContains($acRole->name, $roles);
    }

    public function test_user_assignment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        /** @var ConscriboUser $conUser */
        $conUser = ConscriboUser::factory()
            ->has(ConscriboGroup::factory(), 'groups')
            ->has(ConscriboCommittee::factory()->count(2), 'committees')
            ->create(['email' => $user->email]);

        [$group] = $conUser->groups;
        [$comOne, $comTwo] = $conUser->committees;

        $group->assignRole('member');
        $comTwo->assignRole('board');

        $this->assertNull($user->conscriboUser);
        $this->assertTrue($user->getRoleNames()->isEmpty());

        UpdateConscriboUserJob::dispatchSync($user);

        $user->refresh();

        $this->assertNotNull($user->conscriboUser);

        $allPermissions = $user->getRoleNames();
        $this->assertFalse($allPermissions->isEmpty());
        $this->assertCount(2, $allPermissions);
        $this->assertContains('member', $allPermissions);
        $this->assertContains('board', $allPermissions);
    }
}
