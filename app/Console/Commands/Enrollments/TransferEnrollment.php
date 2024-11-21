<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrollments;

use App\Facades\Enroll;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class TransferEnrollment extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
        enrollment:transfer
            {enrollment : The ID of the enrollment to transfer}
            {user : The user ID of the recipient}
            {reason : The reason for the transfer}
            {--dry-run : Perform a dry run}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfers an enrollment to a different user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find the enrollment
        $enrollment = Enrollment::query()
            ->with('activity', 'user')
            ->find($this->argument('enrollment'));

        if (! $enrollment || ! $enrollment->is_stable) {
            $this->error('The enrollment does not exist or is not stable.');

            return self::INVALID;
        }

        // Find the users
        $sender = $enrollment->user;
        $recipient = User::find($this->argument('user'));

        // Ensure the user is not already registered
        Auth::setUser($recipient);
        $recipientEnrollment = Enroll::getEnrollment($enrollment->activity);
        if ($recipientEnrollment != null) {
            $this->error('The user is already enrolled in the activity.');

            return self::INVALID;
        }

        $this->line("Enrollment ID: <fg=cyan>{$enrollment->id}</>");
        $this->line("Activity: <fg=cyan>{$enrollment->activity->name}</>");
        $this->line("Current user: <fg=cyan>{$enrollment->user->name}</>");
        $this->line("Recipient: <fg=cyan>{$recipient->name}</>");

        // Stop if dry run
        if ($this->option('dry-run')) {
            $this->info('Dry run: the enrollment would be transferred.');

            return self::FAILURE;
        }

        // Find the admin user
        $adminUser = User::find(Config::get('gumbo.admin_id')) ?? User::first();
        Auth::setUser($adminUser);

        Enroll::transferEnrollment($enrollment, $recipient);
        $this->info('The enrollment has been transferred.');

        return self::SUCCESS;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'user' => fn () => search(
                label: 'What user must receive the enrollment:',
                placeholder: 'E.g. Sam Smith',
                options: fn ($value) => strlen($value) > 0
                    ? User::query()
                        ->where('name', 'like', "%{$value}%")
                        ->take(10)
                        ->pluck('name', 'id')
                        ->all()
                    : [],
            ),
            'reason' => fn () => text('What is the reason for the transfer?'),
        ];
    }
}
