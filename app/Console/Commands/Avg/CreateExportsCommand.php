<?php

declare(strict_types=1);

namespace App\Console\Commands\Avg;

use App\Jobs\Avg\CreateUserDataExport;
use App\Models\DataExport;
use Illuminate\Console\Command;

class CreateExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avg:update-exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs data exports that failed to run.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exports = DataExport::query()
            ->whereNull('path')
            ->cursor();

        foreach ($exports as $export) {
            if ($export->refresh()->path !== null) {
                continue;
            }

            $this->line("Running on export {$export->id} for {$export->user->name}...");

            CreateUserDataExport::dispatchSync($export);

            $this->info("Finished export {$export->id}.");
        }
    }
}
