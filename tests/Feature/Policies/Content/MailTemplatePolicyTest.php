<?php

declare(strict_types=1);

namespace Tests\Feature\Policies\Content;

use App\Models\Content\MailTemplate;
use App\Models\User;
use Tests\TestCase;

class MailTemplatePolicyTest extends TestCase
{
    public static function provideTestRoles(): array
    {
        return [
            'member' => ['member', false],
            'ac' => ['ac', false],
            'pr' => ['pr', true],
            'board' => ['board', true],
            'dc' => ['dc', false],
        ];
    }

    /**
     * Ensure if a user can view the template, they're still locked
     * out of changes.
     *
     * @dataProvider provideTestRoles
     */
    public function test_role(string $role, bool $canEdit): void
    {
        $user = User::factory()->withRole([$role])->create();
        $resource = MailTemplate::factory()->create();

        $this->assertEquals($canEdit, $user->can('viewAny', $resource));
        $this->assertEquals($canEdit, $user->can('view', $resource));
        $this->assertFalse($user->can('create', $resource));
        $this->assertFalse($user->can('update', $resource));
        $this->assertFalse($user->can('delete', $resource));
        $this->assertFalse($user->can('restore', $resource));
        $this->assertFalse($user->can('forceDelete', $resource));
    }
}
