<?php

declare(strict_types=1);

namespace App\Console\Commands\Conscribo;

use App\Models\Conscribo\ConscriboOrganisation;
use App\Services\ConscriboService;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrganisationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        conscribo:import-organisations
            {--prune : Prune organisations not seen in the response}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads organisations from Conscribo and stores them in the database.';

    /**
     * Execute the console command.
     */
    public function handle(ConscriboService $conscriboService)
    {
        $relations = $this->downloadAllForEntityType($conscriboService, 'organisatie')->values();

        $this->info(sprintf('Downloaded %d organisations', $relations->count()));

        $bar = $this->output->createProgressBar($relations->count());
        $bar->start();

        $resultCount = 0;

        foreach ($relations as $org) {
            /** @var ConscriboOrganisation $orgModel */
            $orgModel = ConscriboOrganisation::updateOrCreate([
                'conscribo_id' => $org['code'],
            ], [
                'name' => $org['naam'],
                'contract_ends_at' => ! empty($committee['einddatum_contract'])
                    ? Date::createFromFormat('Y-m-d', $committee['einddatum_contract'])->startOfDay()
                    : null,
            ]);

            $resultCount++;
            $bar->advance();

            $this->line(
                sprintf(
                    '%s: Organisation #%03d (%s)',
                    $orgModel->wasRecentlyCreated ? '<fg=green>ADD</>' : '<fg=yellow>UPD</>',
                    $orgModel->id,
                    $orgModel->name,
                ),
                null,
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf('Imported %d organisations.', $resultCount));

        if (! $this->option('prune')) {
            return;
        }

        $result = ConscriboOrganisation::query()
            ->whereNotIn('conscribo_id', $relations->pluck('code'))
            ->delete();

        $this->info(sprintf('Pruned %d organisations.', $result));
    }
}
