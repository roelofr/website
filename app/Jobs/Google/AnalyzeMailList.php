<?php

declare(strict_types=1);

namespace App\Jobs\Google;

use App\Models\Google\GoogleMailList;
use App\Models\Google\GoogleMailListChange;
use App\Services\Google\GroupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeMailList implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private GoogleMailList $mailList;

    /**
     * Create a new job instance.
     */
    public function __construct(GoogleMailList $mailList)
    {
        $this->mailList = $mailList->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(GroupService $googleGroupService): void
    {
        // Download the list from Google
        $model = $this->mailList->loadMissing('conscriboCommittee');

        /** @var null|GoogleMailListChange $lastChange */
        $lastChange = $model->changes()->latest()->first();
        if ($lastChange && $lastChange->finished_at == null) {
            Log::warning('Tried to start a second mail list change, whilst a first one is still pending.', [
                'list' => $model->email,
                'change' => $lastChange->id,
            ]);

            return;
        }

        // Create a change
        $changeModel = $model->changes()->create();

        // Find the Google Model
        $googleModel = $googleGroupService->find($model);

        // Check if a list exists
        if (! $googleModel) {
            $changeModel->update(['action' => 'create']);
            CreateMailList::dispatch($changeModel);

            return;
        }

        // Update the directory_id if it no longer matches.
        if ($googleModel->id !== $model->directory_id) {
            $model->update([
                'directory_id' => $googleModel->id,
            ]);
        }

        // Delete the list if the model is trashed
        if ($model->trashed()) {
            $changeModel->update(['action' => 'delete']);
            DeleteMailList::dispatch($changeModel);

            return;
        }

        // Run a full update
        $changeModel->update(['action' => 'update']);
        UpdateMailList::dispatch($changeModel);
    }
}
