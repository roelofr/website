<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conscribo\ConscriboCommittee;
use App\Models\Google\GoogleMailList;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class UpdateMailListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        app:update-mail-lists
            {--prune : Remove missing lists}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Google Mail List models with data from Conscribo.';

    /**
     * The console command help text.
     *
     * @var string
     */
    protected $help = <<<'HELP'
        This command will update the Google Mail List models with data from Conscribo.

        The data should already be downloaded using the conscribo:import-committees command.
        It will also not dispatch an update to Google Cloud, that's what the google:update-lists
        command is for.

        Basically, this aligns the two "databases" (which are effectively two models).
        HELP;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->createMissingLists();

        $this->updateLists();

        if ($this->option('prune')) {
            $this->pruneLists();
        }

        return self::SUCCESS;
    }

    private function createMissingLists(): void
    {
        $missingLists = ConscriboCommittee::query()
            ->whereNotNull('email')
            ->whereDoesntHave('mailList')
            ->get();

        $this->line(sprintf('Found <fg=green>%s</> missing lists', $missingLists->count()));

        $this->withProgressBar($missingLists, function (ConscriboCommittee $committee) {
            $possibleEmails = Collection::make($committee->email)
                ->concat($committee->aliases)
                ->values();

            $mailList = GoogleMailList::query()
                ->withTrashed()
                ->whereIn('email', $possibleEmails)
                ->first();

            if (! $mailList) {
                $mailList = GoogleMailList::make([
                    'email' => $committee->email,
                ]);
            }

            $mailList->fill([
                'name' => $committee->name,
                'email' => $committee->email,
                'aliases' => $committee->aliases,
            ]);

            $mailList->conscriboCommittee()->associate($committee);
            $mailList->conscribo_id = $committee->conscribo_id;

            $mailList->save();
        });

        $this->newLine();
        $this->info(sprintf('Created <info>%d</> missing lists', $missingLists->count()));
    }

    private function updateLists(): void
    {
        $allLists = ConscriboCommittee::query()
            ->whereNotNull('email')
            ->with('mailList')
            ->get();

        $this->line(sprintf('Found <fg=green>%s</> lists to update', $allLists->count()));

        $this->withProgressBar($allLists, function (ConscriboCommittee $list) {
            $model = $list->googleList;

            $model->fill($list->only('name', 'email', 'aliases'));

            $model->save();
        });

        $this->newLine();
        $this->info(sprintf('Updated <info>%d</> lists', $allLists->count()));
    }

    private function pruneLists(): void
    {
        $removedItems = GoogleMailList::query()
            ->whereDoesntHave('conscriboCommittee')
            ->whereNotNull('conscribo_id')
            ->get();

        $this->line(sprintf('Found <fg=green>%s</> lists to trash', $removedItems->count()));

        $this->withProgressBar($removedItems, function (GoogleMailList $list) {
            $list->delete();
        });

        $this->newLine();
        $this->info(sprintf('Removed <info>%d</> items', $removedItems->count()));
    }
}
