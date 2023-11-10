<?php

declare(strict_types=1);

namespace Tests\Feature\Bots\Commands\Traits;

use App\Bots\Commands\Traits\UsesMessage;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\MessageEntity;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class UsesMessageTest extends TestCase
{
    use UsesMessage;
    use WithFaker;

    private Update $update;

    /**
     * Ensure an update is always present.
     * @before
     */
    public function setupDummyUpdate(): void
    {
        $this->update = new Update([]);
    }

    /**
     * Fallback behaviour.
     */
    public function test_no_update(): void
    {
        // Use $this->update
        $this->assertNull($this->getMessageBody());
        $this->assertNull($this->getMessageCommand());
        $this->assertNull($this->getMessageCommandAndBody());

        // Use param
        $this->assertNull($this->getMessageBody(null));
        $this->assertNull($this->getMessageCommand(null));
        $this->assertNull($this->getMessageCommandAndBody(null));
    }

    /**
     * Non-message Update.
     */
    public function test_non_message_update(): void
    {
        $this->update = new Update([
            'edited_message' => new Message([
                'messageId' => $this->faker->ean13(),
            ]),
        ]);

        // Use $this->update
        $this->assertNull($this->getMessageBody());
        $this->assertNull($this->getMessageCommand());
        $this->assertNull($this->getMessageCommandAndBody());
    }

    /**
     * Test behaviour with non-command message.
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_non_bot_command_message(): void
    {
        $message = new Message([
            'id' => $this->faker->ean13(),
            'text' => $text = $this->faker->sentence(),
            'entities' => [],
        ]);

        $this->assertEquals($text, $this->getMessageBody($message));
        $this->assertNull($this->getMessageCommand($message));

        $this->assertNotNull($result = $this->getMessageCommandAndBody($message));
        $this->assertObjectHasProperty('command', $result);
        $this->assertObjectHasProperty('text', $result);
    }

    public function test_bot_command(): void
    {
        $message = new Message([
            'id' => $this->faker->ean13(),
            'text' => '/hello@bot I am a potato',
            'entities' => [
                new MessageEntity([
                    'type' => 'bot_command',
                    'offset' => 0,
                    'length' => 10,
                ]),
            ],
        ]);

        $this->assertEquals('I am a potato', $this->getMessageBody($message));
        $this->assertEquals('hello', $this->getMessageCommand($message));

        $this->assertNotNull($result = $this->getMessageCommandAndBody($message));
        $this->assertObjectHasProperty('command', $result);
        $this->assertObjectHasProperty('text', $result);
    }
}
