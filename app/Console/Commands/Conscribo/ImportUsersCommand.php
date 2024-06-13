<?php

declare(strict_types=1);

namespace App\Console\Commands\Conscribo;

use App\Models\Conscribo\ConscriboGroup;
use App\Models\Conscribo\ConscriboUser;
use App\Services\ConscriboService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        conscribo:import-users
            {--prune : Prune data not matched in queries}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads groups and users from Conscribo, optionally pruning no longer matched data.';

    /**
     * Indicates whether only one instance of the command can run at any given time.
     *
     * @var bool
     */
    protected $isolated = true;

    /**
     * Execute the console command.
     */
    public function handle(ConscriboService $conscriboService): int
    {
        $this->line('Downloading groups from Conscribo...');
        $groupsWithUsers = $this->downloadGroups($conscriboService);

        $users = Collection::make($groupsWithUsers)->values()->collapse()->unique();
        $this->line(sprintf('Downloading %d users from Conscribo...', $users->count()));
        $this->downloadUsers($conscriboService, $users->all());

        $this->line('Merging users with groups...');
        $this->mergeUsersWithGroups($groupsWithUsers);

        $this->info('Done!');

        return self::SUCCESS;
    }

    protected function downloadGroups(ConscriboService $conscriboService): array
    {
        // Check for a group restraint
        $wantedGroups = Collection::make(Config::get('services.conscribo.settings.groups_to_download', []));
        if ($wantedGroups->isEmpty()) {
            throw new RuntimeException('No groups to download. Check your configuration.');
        }

        // Make the call
        $results = $conscriboService->call('listEntityGroups')['entityGroups'] ?? [];

        // Filter out useful groups only
        $groups = Collection::make($results)
            ->where('type', 'universal')
            ->where('parentId', 'root')
            ->filter(fn ($row) => $wantedGroups->contains(Str::lower($row['name'])))
            ->map(fn ($row) => Arr::only($row, [
                'id',
                'name',
                'parentId',
                'members',
            ]));

        $this->info(sprintf('Downloaded %d groups, filtered down to %d results', count($results), $groups->count()));

        $bar = $this->output->createProgressBar($groups->count());
        $bar->start();

        // Create groups and keep track of user IDs
        $groupsWithUserIds = [];
        foreach ($groups as $group) {
            /** @var ConscriboGroup $groupModel */
            $groupModel = ConscriboGroup::updateOrCreate([
                'conscribo_id' => $group['id'],
            ], [
                'name' => $group['name'],
            ]);

            $bar->advance();

            $this->line(
                sprintf(
                    '%s: Group #%03d (%s)',
                    $groupModel->wasRecentlyCreated ? '<fg=green>ADD</>' : '<fg=yellow>UPD</>',
                    $groupModel->id,
                    $groupModel->name,
                ),
                null,
                OutputInterface::VERBOSITY_VERBOSE,
            );

            $eligibleMembers = Collection::make($group['members'] ?? [])
                ->where('entityType', 'persoon')
                ->pluck('entityId');

            $groupsWithUserIds[$groupModel->id] = $eligibleMembers->all();
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf('Imported %d groups.', $groups->count()));

        if ($this->option('prune')) {
            $result = ConscriboGroup::query()
                ->whereNotIn('conscribo_id', $groups->pluck('id'))
                ->delete();

            $this->info(sprintf('Pruned %d groups.', $result));
        }

        return $groupsWithUserIds;
    }

    protected function downloadUsers(ConscriboService $conscriboService, array $userIds): void
    {
        $response = $this->downloadAllForEntityType($conscriboService, 'persoon');

        $wantedUsers = $response
            ->whereIn('code', $userIds)
            ->filter(fn ($row) => ! empty($row['email']))
            ->values();

        $this->info(sprintf('Downloaded %d users, filtered down to %d results', $response->count(), $wantedUsers->count()));

        $bar = $this->output->createProgressBar($wantedUsers->count());
        $bar->start();
        $resultCount = 0;

        foreach ($wantedUsers as $user) {
            $userModel = ConscriboUser::updateOrCreate([
                'conscribo_id' => $user['code'],
            ], [
                'conscribo_selector' => $user['selector'],
                'first_name' => $user['voornaam'],
                'infix' => $user['tussenvoegsel'],
                'last_name' => $user['naam'],
                'email' => $user['email'],
                'address' => Collection::make([
                    'street' => $user['straat'],
                    'number' => $user['huisnr'],
                    'number_suffix' => $user['huisnr_toev'],
                    'zip' => $user['postcode'],
                    'city' => $user['plaats'],
                    'formatted' => $user['adres'],
                ]),
            ]);

            $resultCount++;
            $bar->advance();

            $this->line(
                sprintf(
                    '%s: User #%03d (%s)',
                    $userModel->wasRecentlyCreated ? '<fg=green>ADD</>' : '<fg=yellow>UPD</>',
                    $userModel->id,
                    $userModel->name,
                ),
                null,
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf('Imported %d users.', $resultCount));

        if (! $this->option('prune')) {
            return;
        }

        $result = ConscriboUser::query()
            ->whereNotIn('conscribo_id', $wantedUsers->pluck('code'))
            ->delete();

        $this->info(sprintf('Pruned %d users.', $result));
    }

    protected function mergeUsersWithGroups(array $groupUserIdMapping): void
    {
        $bar = $this->output->createProgressBar(count($groupUserIdMapping));
        $bar->start();

        foreach ($groupUserIdMapping as $groupId => $userIds) {
            ConscriboGroup::findOrFail($groupId)->users()->sync(
                ConscriboUser::query()
                    ->whereIn('conscribo_id', $userIds)
                    ->pluck('id'),
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->line(sprintf('Provisioned %d groups with users.', count($groupUserIdMapping)));
    }
}
