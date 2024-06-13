<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use App\Jobs\Google\AnalyzeMailList;
use App\Models\Google\GoogleMailList;
use Closure;
use http\Exception\RuntimeException;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateListsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        google:update-lists
            {list? : ID, email, alias or directory_id of the list}
            {--all : Update all lists}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates one or all of the Google List from the local storage to the Google Directory.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $listIdentifier = $this->argument('list');
        $list = GoogleMailList::withTrashed()
            ->when($listIdentifier, $this->scopeToIdentifier($listIdentifier))
            ->lazy(10);

        if ($listIdentifier && $list->isEmpty()) {
            $this->error('Failed to find any matching list');

            return self::INVALID;
        }

        $this->withProgressBar($list, fn ($row) => AnalyzeMailList::dispatchSync($row));

        $this->info('Done');

        return self::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output): int
    {
        $wantsAll = $this->option('all');
        $wantsSingle = $this->argument('list');

        if ($wantsAll xor $wantsSingle) {
            return self::SUCCESS;
        }

        if ($wantsAll && $wantsSingle) {
            $this->error('You must specify either a single list or --all.');
        }

        if (confirm('Would you like to update all lists?', false)) {
            $input->setOption('all', true);
            $input->setArgument('list', null);

            return self::SUCCESS;
        }

        $user = search(
            'Which list would you like to update?',
            fn (string $arg) => GoogleMailList::query()
                ->where($this->scopeToIdentifier($arg))
                ->take(10)
                ->pluck('email', 'email')
                ->all(),
        );

        if (! $user) {
            $this->error('No user found.');

            throw new RuntimeException('No user was specified and --all was not given.');
        }

        $input->setArgument('user', $user);
        $input->setOption('all', false);

        return self::SUCCESS;
    }

    private function scopeToIdentifier(string $identifier): Closure
    {
        return fn ($query) => $query->orWhere([
            ['id', '=', $identifier],
            ['email', 'like', "%{$identifier}%"],
            ['aliases', 'like', "%{$identifier}%"],
            ['directory_id', '=', $identifier],
        ]);
    }
}
