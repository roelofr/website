<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Google;

use App\Models\Google\GoogleMailList;
use App\Models\Google\GoogleMailListChange;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class GoogleMailListChangeTest extends TestCase
{
    public function test_change_keeping(): void
    {
        /** @var GoogleMailList $list */
        $list = GoogleMailList::factory()->create();

        /** @var GoogleMailListChange $change */
        $change = $list->changes()->create();

        $this->assertCount(0, $change->data);
        $this->assertNull($change->started_at);
        $this->assertNull($change->finished_at);

        $change->addChange('alias', 'add', 'alias@example.com');

        $this->assertCount(1, $change->data);
        $this->assertNotNull($change->started_at);
        $this->assertNull($change->finished_at);
    }

    public function test_no_change_on_start_date(): void
    {
        /** @var GoogleMailList $list */
        $list = GoogleMailList::factory()->create();

        /** @var GoogleMailListChange $change */
        $change = $list->changes()->create([
            'started_at' => Date::now(),
        ]);

        $initialStartedAt = $change->started_at;

        $this->assertCount(0, $change->data);
        $this->assertNotNull($change->started_at);
        $this->assertNull($change->finished_at);

        $this->travel(5)->minutes();

        $change->addChange('alias', 'add', 'test');

        $this->assertCount(1, $change->data);
        $this->assertEquals($initialStartedAt, $change->started_at);
        $this->assertNull($change->finished_at);
    }
}
