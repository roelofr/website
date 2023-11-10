<?php

declare(strict_types=1);

namespace App\Bots\Commands;

use App\Enums\Models\BotQuoteType;
use App\Models\BotQuote;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Telegram\Bot\Actions;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Message;

/**
 * @codeCoverageIgnore
 */
class QuoteCommand extends Command
{
    private const REPLY_TO_SHORT = <<<'MSG'
    Geef wist-je-datje 😠

    <code>/wjd [bericht]</code>
    MSG;

    private const MAX_QUOTE_LENGTH = 210;

    private const REPLY_TOO_LONG = <<<'MSG'
    🤌 Moet Korter 🤌

    Je verhaal is te lang, maak het maar wat mand.
    MSG;

    private const REPLY_GUEST_THROTTLED = <<<'MSG'
    ⏰ Sorry, je mag nog geen wist-je-datje insturen.

    Log in via /login om deze beperking weg te halen.
    MSG;

    private const REPLY_FAILED = <<<'MSG'
    Oops, er is iets fout gegaan bij het opslaan 😬
    MSG;

    private const REPLY_QUOTE_OK = <<<'MSG'
    <quote>%s</quote>

    Hebben we, je uitspraak is opgeslagen!
    MSG;

    private const REPLY_FACT_OK = <<<'MSG'
    Je wist-je-datje is opgeslagen.

    <quote><b>Wist je dat...</b> %s</quote>
    MSG;

    private const REPLY_PUBLIC = <<<'MSG'
    🤫 Stil houden? Stuur je zooi via een DM.
    MSG;

    private const REPLY_GUEST = <<<'MSG'
    Je bent niet ingelogd en mag maar beperkt wist-je-datjes sturen.
    Limiet verwijderen? Log even in via /login (in een DM).
    MSG;

    /**
     * The name of the Telegram command.
     *
     * @var string
     */
    protected $name = 'wjd';

    /**
     * Command Aliases - Helpful when you want to trigger command with more than one name.
     *
     * @var array<string>
     */
    protected $aliases = [
        'quote',
        'wistjedat',
        'wistjedatje',
    ];

    /**
     * The Telegram command description.
     *
     * @var string
     */
    protected $description = 'Stuur een wist-je-datje of quote in.';

    /**
     * Command Argument Pattern.
     *
     * @var string
     */
    protected $pattern = '.+';

    public function buildBotQuote(
        Message $message,
        ?User $user,
    ): BotQuote {
        /** @var \Telegram\Bot\User */
        $sender = $message->from;

        if (! $sender) {
            throw new RuntimeException('This message appears to have no sender!');
        }

        $pureMessage = $this->getMessageBody($message);
        if (! $pureMessage) {
            throw new InvalidArgumentException('Failed to process message!');
        }

        $messageLines = Str::of($pureMessage)->trim()->split("\n")->filter(fn (string $value) => ! empty(trim($value)));
        $quoteLines = $messageLines
            ->filter(fn (string $value) => (bool) preg_match('/^[“"‘\'](.+)[”"’\'](\s?-\s?)([a-z].+)$/i', $value));

        // A quote is a quote if at least 75% of the message looks like a quote
        $isAQuote = ($quoteLines->count() / $messageLines->count() >= 0.75);

        return BotQuote::create([
            'user_id' => $user?->id,
            'message_id' => $message->message_id,

            'quote' => $messageLines->join("\n"),
            'quote_type' => $isAQuote ? BotQuoteType::QUOTE : BotQuoteType::FACT,
            'display_name' => trim("{$sender->firstName} {$sender->lastName}") ?: "#{$sender->id}",
        ]);
    }

    /**
     * Handle the activity.
     */
    public function handle()
    {
        $message = $this->update->message;
        if (! $message) {
            return;
        }

        // Check the quote, remove the @Username if found
        $quoteText = $this->getMessageBody();

        //check if quote is unique
        $messageId = $message->messageId;
        if (BotQuote::where('message_id', $messageId)->exists()) {
            return;
        }

        // Send typing status
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        Log::info('Derrived quote {quote} from {message}.', [
            'quote' => $quoteText,
            'message' => $this->getUpdate()->getMessage()->getText(),
        ]);

        if (empty($quoteText)) {
            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_TO_SHORT),
                'parse_mode' => 'HTML',
            ]);

            return;
        }

        // Get user
        $tgUser = $this->getTelegramUser();
        $user = $this->getUser();

        if (Str::length($quoteText) > self::MAX_QUOTE_LENGTH) {
            Log::warning('Sender {user} sent overly long quote of {length} characters {quote}', [
                'user' => $user ?? $tgUser,
                'length' => Str::length($quoteText),
                'quote' => Str::limit($quoteText, 500),
            ]);

            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_TOO_LONG),
                'parse_mode' => 'HTML',
            ]);

            return;
        }

        // Rate-limit if a guest
        if (! $user && $this->rateLimit('quotes', self::REPLY_GUEST_THROTTLED, 'PT6H')) {
            return;
        }

        $model = null;

        try {
            $model = $this->buildBotQuote($message, $user);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Failed to save quote for {message}: {exception}', [
                'message' => $message,
                'exception' => $exception,
            ]);

            $this->replyWithMessage(self::REPLY_FAILED);
            $this->forgetRateLimit('quotes');

            return;
        }

        // Prep the reply based on the recognized type.
        $preparedMessage = $this->formatText(
            $model->quote_type === BotQuoteType::QUOTE ? self::REPLY_QUOTE_OK : self::REPLY_FACT_OK,
            e($model->quote),
        );

        // Add a "keep it quiet" group-chat suffix
        if ($this->isInGroupChat()) {
            $preparedMessage .= PHP_EOL . PHP_EOL . self::REPLY_PUBLIC;
        }

        // Add a keyboard when logged in
        $keyboard = $user ? Keyboard::make()->inline()->row(
            Keyboard::inlineButton([
                'text' => 'Bekijk mijn wist-je-datjes',
                'url' => route('account.quotes'),
            ]),
        ) : null;

        // Send messages
        $this->replyWithMessage(array_filter([
            'text' => $preparedMessage,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message->messageId,
            'reply_markup' => $keyboard,
        ]));

        // Render guest response
        if (! $user) {
            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_GUEST),
                'disable_notification' => true,
            ]);
        }
    }
}
