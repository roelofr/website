<?php

declare(strict_types=1);

namespace App\Console\Commands\Conscribo;

use App\Models\Conscribo\ConscriboCommittee;
use App\Models\Conscribo\ConscriboUser;
use App\Models\Role;
use App\Services\ConscriboService;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommitteesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        conscribo:import-committees
            {--prune : Prune committees not seen in the response}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads committees from Conscribo and stores them in the database.';

    /**
     * Execute the console command.
     */
    public function handle(ConscriboService $conscriboService)
    {
        $relations = $this->downloadAllForEntityType($conscriboService, 'commissie')->values();

        $this->info(sprintf('Downloaded %d committees', $relations->count()));

        $bar = $this->output->createProgressBar($relations->count());
        $bar->start();

        $resultCount = 0;

        foreach ($relations as $committee) {
            /** @var ConscriboCommittee $committeeModel */
            $committeeModel = ConscriboCommittee::updateOrCreate([
                'conscribo_id' => $committee['code'],
            ], [
                'name' => $committee['naam'],
                'email' => $committee['e_mailadres'],
                'aliases' => Str::of($committee['aliassen'])->explode(',')->map(fn ($alias) => trim($alias))->values(),
            ]);

            // Assign a role if one was already created by it's name.
            if ($committeeModel->wasRecentlyCreated) {
                $emailPrefix = Str::before($committee['e_mailadres'], '@');

                $role = Role::query()->where(fn ($query) => $query->orWhere([
                    ['name', $emailPrefix],
                    ['title', $committeeModel->name],
                ]))->pluck('id');

                if ($role->isNotEmpty()) {
                    $committeeModel->roles()->sync($role);
                }
                //
            }

            $expectedMembers = ConscriboUser::query()
                ->whereIn('conscribo_selector', Str::of($committee['leden'])->explode(',')->map(fn ($selector) => trim($selector)))
                ->get();

            $ownerLabel = $committee['voorzitter'];

            $committeeModel->members()->sync(
                $expectedMembers->mapWithKeys(fn ($member) => [$member->id => [
                    'is_owner' => $member->conscribo_name == $ownerLabel,
                ]]),
            );

            $resultCount++;
            $bar->advance();

            $this->line(
                sprintf(
                    '%s: Committee #%03d (%s)',
                    $committeeModel->wasRecentlyCreated ? '<fg=green>ADD</>' : '<fg=yellow>UPD</>',
                    $committeeModel->id,
                    $committeeModel->name,
                ),
                null,
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf('Imported %d committees.', $resultCount));

        if (! $this->option('prune')) {
            return;
        }

        $result = ConscriboCommittee::query()
            ->whereNotIn('conscribo_id', $relations->pluck('code'))
            ->delete();

        $this->info(sprintf('Pruned %d committees.', $result));
    }
}
