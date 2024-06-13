<?php

declare(strict_types=1);

namespace App\Console\Commands\Avg;

use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;

class PruneEnrollmentDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avg:prune-enrollment-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old enrollment data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = Enrollment::query()
            ->whereHas('activity', fn ($query) => $query->where('end_date', '<', Date::now()->sub(Config::get('gumbo.retention.enrollment-data'))))
            ->whereNotNull('data')
            ->update(['data' => null]);

        $this->info("Pruned {$count} enrollment datasets");

        return Command::SUCCESS;
    }
}
