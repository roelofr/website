<?php

declare(strict_types=1);

namespace App\Jobs\Google;

use App\Models\Google\GoogleMailListChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMailList implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private GoogleMailListChange $changeModel)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Implement handle() method.
    }
}
