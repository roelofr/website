<?php

declare(strict_types=1);

namespace App\Bots\Commands\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\MessageEntity;

trait UsesMessage
{
    /**
     * Processes the message to find the body and command using the message entities.
     * @param null|Message $message message to process, leave null if you want to process the Update
     */
    protected function getMessageCommandAndBody(?Message $message = null): ?object
    {
        // Ensure a message is set
        $message ??= $this->update->message;
        if (! $message) {
            return null;
        }

        // Find params
        $messageText = $message->text;
        $botCommandEntity = Arr::first($message->entities, fn (MessageEntity $messageEntity) => $messageEntity->type === 'bot_command');

        // Skip if no commands are present (weird, but okay)
        if (! $botCommandEntity instanceof MessageEntity) {
            return (object) [
                'command' => null,
                'text' => $messageText,
            ];
        }

        // Find the exact spot of the bot command
        $startOfCommand = mb_strpos($messageText, '/', max(0, $botCommandEntity->offset - 1));

        // Return the proper strings
        return (object) [
            'command' => mb_substr($messageText, $startOfCommand, $botCommandEntity->length),
            'text' => trim(mb_substr($messageText, $startOfCommand + $botCommandEntity->length)),
        ];
    }

    /**
     * Returns the body without the invoked command.
     * @param null|Message $message message to process, leave null if you want to process the Update
     */
    protected function getMessageBody(?Message $message = null): ?string
    {
        return $this->getMessageCommandAndBody($message)?->text;
    }

    /**
     * Returns the command name, without the bot name.
     * @param null|Message $message message to process, leave null if you want to process the Update
     */
    protected function getMessageCommand(?Message $message = null): ?string
    {
        $command = $this->getMessageCommandAndBody($message)?->command;

        return $command ? (string) Str::of($command)->beforeLast('@')->ltrim('/') : null;
    }
}
