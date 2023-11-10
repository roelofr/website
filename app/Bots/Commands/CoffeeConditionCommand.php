<?php

declare(strict_types=1);

namespace App\Bots\Commands;

use App\Bots\Services\CoffeeConditionService;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * @codeCoverageIgnore
 */
class CoffeeConditionCommand extends Command
{
    public const VALID_COFFEE_STRINGS = [
        'zet' => true,
        'gezet' => true,
        'fresh' => true,
        'brew' => true,
        'weg' => false,
        'op' => false,
        'gone' => false,
        'out' => false,
    ];

    /**
     * The name of the Telegram command.
     *
     * @var string
     */
    protected $name = 'koffie';

    /**
     * The Telegram command description.
     *
     * @var string
     */
    protected $description = 'Meld of bekijk de koffie conditie.';

    /**
     * Handle the activity.
     */
    public function handle()
    {
        // Fetch user
        $user = $this->getUser();
        if (! $this->ensureIsMember($user)) {
            return;
        }

        /** @var CoffeeConditionService $service */
        $service = App::make(CoffeeConditionService::class);

        // Check message
        $secondWordInMessage = Str::of($this->getMessageBody())->words(1, '')->trim()->lower();
        if ($secondWordInMessage->isEmpty()) {
            $condition = $service->getCoffeeCondition();

            $this->replyWithMessage(['text' => $condition]);

            return;
        }

        if (! array_key_exists($secondWordInMessage, self::VALID_COFFEE_STRINGS)) {
            $this->replyWithMessage(['text' => __('Invalid coffee condition, please use one of the following: :conditions', [
                'conditions' => 'gezet, op',
            ])]);

            return;
        }

        $this->replyWithMessage([
            'text' => $service->setCoffee($user, self::VALID_COFFEE_STRINGS[$secondWordInMessage]),
        ]);
    }
}
