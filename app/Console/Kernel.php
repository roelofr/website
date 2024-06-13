<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\SendBotQuotes;
use App\Jobs\UpdateEnrollmentUserTypes;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Config;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * HEADS UP
     * Never schedule stuff between 02:00 - 03:00 due to Daylight Saving Time.
     * Events might not run in that period.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Pruning
        $this->registerPruningCommands($schedule);

        // Backups
        $schedule->command('backup:create')->daily()->at('01:00');
        $schedule->command('backup:create', ['--full' => true])->weekly()->at('01:30');
        $schedule->command('backup:images')->weeklyOn(0, '06:30');

        // Ensure Google Wallet data is updated, if enabled
        if (Config::get('services.google.wallet.enabled', false)) {
            $schedule->command('google-wallet:prune-nonces')->daily();
            $schedule->command('google-wallet:create-missing-activities')->twiceDailyAt(offset: 10);
            $schedule->command('google-wallet:activity', ['--with-enrollments'])->twiceDailyAt(offset: 20);
        }

        // Update enrollments' user_type every day
        $schedule->job(UpdateEnrollmentUserTypes::class)->daily();

        // Run a Conscribo update every hour
        $schedule->command('conscribo:import')->hourlyAt(15);
        $schedule->command('app:update-user', ['--all' => true])->hourlyAt(20);

        // Run a Conscribo prune every morning
        $schedule->command('conscribo:import', ['--prune' => true])->dailyAt('07:45');
        $schedule->command('app:update-user', ['--all' => true, '--prune' => true])->dailyAt('07:50');

        // Update Sponsor logos nightly (in case some were missed)
        $schedule->command('gumbo:update-sponsor-logos')->dailyAt('05:00');

        // Update shop twice a day
        $schedule->command('shop:update')->twiceDaily(11, 23);

        // Send feature mails on common times
        $schedule->command('gumbo:send-activity-feature-mails')->cron('15 2,7,12,16,21 * * *');
        $schedule->command('gumbo:activity:send-messages')->everyFiveMinutes();
        $schedule->command('gumbo:activity:send-system-messages', ['--send'])->hourlyAt(10);

        // Updated maillists every morning
        $schedule->command('gumbo:update-lists')->dailyAt('06:00');

        // Send payment reminders on fridays at 20:30
        $schedule->command('enrollment:send-reminders')->fridays()->at('20:30');

        // Update ticket PDFs from enrollments hourly
        $schedule->command('gumbo:update-ticket-pdfs')->hourly();

        // Send quotes weekly, if Telegram bot is configured
        if (Config::get('telegram.bots.gumbot.token')) {
            $schedule->job(SendBotQuotes::class)->weeklyOn(1, '08:15');
            $schedule->command('bot:update')->hourly();
        }

        // Manually check payments every 30 minutes
        $schedule->command('payments:update', ['--all'])->everyThirtyMinutes();

        // Shop expiration and reminders
        $schedule->command('shop:send-reminders')->hourlyAt(20);
        $schedule->command('shop:cancel-expired')->twiceDaily(2, 14);

        // Update Telegram Gifs every now and then
        $schedule->command('tenor:preload-gifs', ['--prune'])->thursdays()->at('03:33');

        // Update the Mollie settlements every night, when an org key is set
        if (Config::get('mollie.org_key')) {
            $schedule->command('payments:settlements')->dailyAt('06:00');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

    private function registerPruningCommands(Schedule $schedule): void
    {
        // Hourly
        $schedule->command('enrollment:prune')->everyThirtyMinutes();
        $schedule->command('gumbo:prune-webcams')->everyFourHours();
        $schedule->command('gumbo:prune-gallery-filepond')->hourly();

        // Daily
        $schedule->command('telescope:prune')->daily();
        $schedule->command('model:prune')->daily();
        $schedule->command('queue:prune-failed')->daily();
        $schedule->command('queue:prune-batches')->daily();
        $schedule->command('enrollment:prune-exports')->daily();
        $schedule->command('sanctum:prune-expired')->daily();
        $schedule->command('gumbo:prune-gallery-filepond', ['--prune', '--clean'])->daily();
        $schedule->command('gumbo:prune-glide-cache')->daily();

        // Less frequent
        $schedule->command('avg:prune-exports')->weekly();
        $schedule->command('avg:prune-enrollment-data')->twiceMonthly();
    }
}
