<?php

declare(strict_types=1);

namespace App\Console\Commands\Conscribo;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        conscribo:import
            {--prune : Prune data not matched in queries}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads Conscribo data, optionally pruning stuff.';

    /**
     * The console command help text.
     *
     * @var string
     */
    protected $help = <<<'HELP'
        This command downloads data from Conscribo and imports it into the database.
        It can optionally prune data that is not matched in queries.

        Data updated includes:
        - List of groups
        - List of users in those groups
        - Committees and their members
        - Organisations (usually sponsors)
        HELP;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('conscribo:import-users', [
            '--prune' => $this->option('prune'),
        ]);

        $this->call('conscribo:import-committees', [
            '--prune' => $this->option('prune'),
        ]);

        $this->call('conscribo:import-organisations', [
            '--prune' => $this->option('prune'),
        ]);

        $this->info('Import completed!');

        return self::SUCCESS;
    }
}
