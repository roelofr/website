<?php

declare(strict_types=1);

namespace App\Console\Commands\Avg;

use App\Models\DataExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

class PruneExpiredExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avg:prune-exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes expired exports and their data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exports = DataExport::query()
            ->where('expires_at', '<', Date::now())
            ->get();

        foreach ($exports as $export) {
            if ($export->path) {
                Storage::exists($export->path) && Storage::delete($export->path);

                $this->info("Deleted file for {$export->id}");

                $export->path = null;
                $export->save();
            }

            $export->delete();
        }
    }
}
