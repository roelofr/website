<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs\Google;

use App\Jobs\Google\AnalyzeMailList;
use App\Models\Google\GoogleMailList;
use App\Services\Google\GroupService;
use Google\Service\Directory\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class AnalyzeMailListTest extends TestCase
{
    use WithFaker;

    public function test_new_mail_list_creation(): void
    {
        /** @var GoogleMailList $listModel */
        $listModel = GoogleMailList::factory()->create();

        $groupService = $this->mock(GroupService::class);
        $groupService->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $this->assertDatabaseMissing('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);

        Bus::fake([
            \App\Jobs\Google\CreateMailList::class,
        ]);

        AnalyzeMailList::dispatchSync($listModel);

        Bus::assertDispatched(\App\Jobs\Google\CreateMailList::class);

        $this->assertDatabaseHas('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);
    }

    public function test_existing_list_creation(): void
    {
        $listId = $this->faker->word();

        /** @var GoogleMailList $listModel */
        $listModel = GoogleMailList::factory()->create([
            'directory_id' => $listId,
        ]);

        $googleModel = Mockery::mock(Group::class);
        $googleModel->id = $listId;

        $groupService = $this->mock(GroupService::class);
        $groupService->shouldReceive('find')
            ->once()
            ->andReturn($googleModel);

        $this->assertDatabaseMissing('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);

        Bus::fake([
            \App\Jobs\Google\UpdateMailList::class,
        ]);

        AnalyzeMailList::dispatchSync($listModel);

        $this->assertSame($listId, $listModel->fresh()->directory_id);

        Bus::assertDispatched(\App\Jobs\Google\UpdateMailList::class);

        $this->assertDatabaseHas('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);
    }

    public function test_deleted_locally(): void
    {
        $listId = $this->faker->word();

        /** @var GoogleMailList $listModel */
        $listModel = GoogleMailList::factory()->create([
            'directory_id' => $listId,
        ]);
        $listModel->delete();

        $googleModel = Mockery::mock(Group::class);
        $googleModel->id = $listId;

        $groupService = $this->mock(GroupService::class);
        $groupService->shouldReceive('find')
            ->once()
            ->andReturn($googleModel);

        $this->assertDatabaseMissing('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);

        Bus::fake([
            \App\Jobs\Google\DeleteMailList::class,
        ]);

        AnalyzeMailList::dispatchSync($listModel);

        $this->assertSame($listId, $listModel->fresh()->directory_id);

        Bus::assertDispatched(\App\Jobs\Google\DeleteMailList::class);

        $this->assertDatabaseHas('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);
    }

    public function test_directory_id_reassign(): void
    {
        $initialListId = $this->faker->word();
        $actualListId = $this->faker->word();

        /** @var GoogleMailList $listModel */
        $listModel = GoogleMailList::factory()->create([
            'directory_id' => $initialListId,
        ]);

        $googleModel = Mockery::mock(Group::class);
        $googleModel->id = $actualListId;

        $groupService = $this->mock(GroupService::class);
        $groupService->shouldReceive('find')
            ->once()
            ->andReturn($googleModel);

        $this->assertDatabaseMissing('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);

        Bus::fake([
            \App\Jobs\Google\UpdateMailList::class,
        ]);

        AnalyzeMailList::dispatchSync($listModel);

        $this->assertSame($actualListId, $listModel->fresh()->directory_id);

        Bus::assertDispatched(\App\Jobs\Google\UpdateMailList::class);

        $this->assertDatabaseHas('google_mail_list_changes', [
            'google_mail_list_id' => $listModel->id,
        ]);
    }
}
