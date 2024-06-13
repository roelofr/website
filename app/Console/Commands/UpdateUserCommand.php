<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\UpdateConscriboUserJob;
use App\Models\User;
use http\Exception\RuntimeException;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates Users with data from ConscriboUser models.
 */
class UpdateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
            app:update-user
                {user? : User to update}
                {--all : Update all users}
                {--prune : Include users without verified email addresses}
        CMD;

    /**
     * The console command name aliases.
     */
    protected $aliases = ['gumbo:user:update'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates users using the data stored locally from Conscribo.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::query()
            ->unless($this->option('prune'), fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($user = $this->argument('user'), fn ($query) => $query->whereEmail($user))
            ->get();

        if ($users->isEmpty()) {
            $this->error('Failed to find any matching user');

            return self::INVALID;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            UpdateConscriboUserJob::dispatchSync($user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('User update complete.');

        return self::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output): int
    {
        $wantsAll = $this->option('all');
        $wantsSingle = $this->argument('user');

        if ($wantsAll xor $wantsSingle) {
            return self::SUCCESS;
        }

        if ($wantsAll && $wantsSingle) {
            $this->error('You must specify either a single user or all users.');
        }

        if (confirm('Would you like to update all users?', false)) {
            $input->setOption('all', true);
            $input->setArgument('user', null);

            return self::SUCCESS;
        }

        $user = search(
            'Which user would you like to update?',
            fn (string $input) => User::query()
                ->where(
                    fn ($query) => $query
                        ->where('name', 'like', "%{$input}%")
                        ->orWhere('email', 'like', "%{$input}%")
                        ->orWhere('id', $input),
                )
                ->take(10)
                ->pluck('name', 'email')
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
}
