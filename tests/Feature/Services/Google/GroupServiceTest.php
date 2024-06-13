<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Google;

use App\Models\Google\GoogleMailList;
use App\Services\Google\GroupService;
use Google\Service\Directory as DirectoryService;
use Google\Service\Directory\Group as GroupModel;
use Google\Service\Directory\Resource\Groups as GroupsResource;
use Google\Service\Exception as ServiceException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GroupServiceTest extends TestCase
{
    use WithFaker;

    private GroupsResource|Mockery\MockInterface $groupsMock;

    /**
     * @before
     */
    public function setupMockBeforeTest(): void
    {
        $this->afterApplicationCreated(function () {
            $mockService = $this->mock(DirectoryService::class);
            $this->groupsMock = $mockService->groups = Mockery::mock(GroupsResource::class);
        });
    }

    public function test_get_valid(): void
    {
        $foundGroup = $this->createRandomGroupModel();
        $this->groupsMock->shouldReceive('get')
            ->with('directory_id')
            ->once()
            ->andReturn($foundGroup);

        $model = GoogleMailList::factory()->create([
            'directory_id' => 'directory_id',
        ]);

        /** @var GroupService $service */
        $service = App::make(GroupService::class);
        $this->assertSame($foundGroup, $service->get($model));
    }

    public function test_get_invalid(): void
    {
        $notFoundException = new ServiceException('Not found', 404);
        $this->groupsMock->shouldReceive('get')
            ->with('directory_id')
            ->once()
            ->andThrow($notFoundException);

        $model = GoogleMailList::factory()->create([
            'directory_id' => 'directory_id',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find Google Group');

        App::make(GroupService::class)->get($model);
    }

    public function test_get_unset(): void
    {
        $model = GoogleMailList::factory()->create([
            'directory_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mail list is missing a directory ID!');

        App::make(GroupService::class)->get($model);
    }

    public function test_find_happy_trail(): void
    {
        $mockService = $this->mock(DirectoryService::class);
        $groupsMock = $mockService->groups = Mockery::mock(GroupsResource::class);

        $notFoundException = new ServiceException('Not found', 404);
        $foundGroup = Mockery::mock(GroupModel::class);

        $groupsMock->shouldReceive('get')
            ->with(Mockery::anyOf('email', 'alias1'))
            ->times(2) // alias2 should never be checked
            ->andReturnUsing(function ($value) use ($notFoundException, $foundGroup) {
                return match ($value) {
                    'alias1' => $foundGroup,
                    default => throw $notFoundException,
                };
            });

        $model = GoogleMailList::factory()->create([
            'email' => 'email',
            'aliases' => ['alias1', 'alias2'],
        ]);

        $service = App::make(GroupService::class);
        $this->assertSame($foundGroup, $service->find($model));
    }

    public function test_find_with_invalid_directory_id(): void
    {
        $mockService = $this->mock(DirectoryService::class);
        $groupsMock = $mockService->groups = Mockery::mock(GroupsResource::class);

        $notFoundException = new ServiceException('Not found', 404);
        $foundGroup = Mockery::mock(GroupModel::class);

        $groupsMock->shouldReceive('get')
            ->with(Mockery::anyOf('directory_id', 'email', 'alias1'))
            ->times(3) // alias2 should never be checked
            ->andReturnUsing(function ($value) use ($notFoundException, $foundGroup) {
                return match ($value) {
                    'alias1' => $foundGroup,
                    default => throw $notFoundException,
                };
            });

        $model = GoogleMailList::factory()->create([
            'directory_id' => 'directory_id',
            'email' => 'email',
            'aliases' => ['alias1', 'alias2'],
        ]);

        Log::spy()->shouldReceive('warning')
            ->with(Mockery::pattern('/is missing in Google/'), Mockery::any())
            ->atLeast()
            ->once();

        $service = App::make(GroupService::class);
        $this->assertSame($foundGroup, $service->find($model));
    }

    public function test_find_nothing(): void
    {
        $mockService = $this->mock(DirectoryService::class);
        $groupsMock = $mockService->groups = Mockery::mock(GroupsResource::class);

        $notFoundException = new ServiceException('Not found', 404);

        $groupsMock->shouldReceive('get')
            ->with(Mockery::anyOf('email', 'alias1', 'alias2'))
            ->times(3)
            ->andThrow($notFoundException);

        $model = GoogleMailList::factory()->create([
            'email' => 'email',
            'aliases' => ['alias1', 'alias2'],
        ]);

        $service = App::make(GroupService::class);
        $this->assertNull($service->find($model));
    }

    private function createRandomGroupModel(): GroupModel
    {
        $foundGroup = Mockery::mock(GroupModel::class);

        $foundGroup->id = $this->faker->randomAscii();
        $foundGroup->email = $this->faker->email();

        return $foundGroup;
    }
}
