<?php

declare(strict_types=1);

namespace App\Bots\Commands;

use Telegram\Bot\Commands\Command as TelegramCommand;

/**
 * @codeCoverageIgnore
 */
abstract class Command extends TelegramCommand
{
    use Traits\UsesMessage;
    use Traits\UsesPreloadedGifs;
    use Traits\UsesRateLimits;
    use Traits\UsesUsersAndChats;

    /**
     * Runs a string through sprintf, and unwraps single newlines.
     */
    public function formatText(string $text, ...$args): string
    {
        $out = sprintf($text, ...$args);

        return preg_replace('/(?<!\n)\n(?=\S)/', ' ', $out);
    }
}
