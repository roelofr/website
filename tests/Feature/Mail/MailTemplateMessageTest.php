<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\MailTemplateMessage;
use App\Models\Content\MailTemplate;
use App\Models\Data\Content\MailTemplateParam;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class MailTemplateMessageTest extends TestCase
{
    private static function makeMailTemplate(string $body, array $params = []): MailTemplate
    {
        $params = collect($params)->map(fn ($row) => new MailTemplateParam(
            name: $row,
            description: null,
        ))->values();

        $label = sprintf('template_%s', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']);

        return MailTemplate::factory()->create([
            'label' => $label,
            'subject' => 'Test Message',
            'params' => $params,
            'body' => $body,
        ]);
    }

    /**
     * Test a simple mail without any parameters.
     */
    public function test_empty(): void
    {
        $template = self::makeMailTemplate(<<<'DOC'
        Hey,

        Thanks!

        Cheers
        DOC);

        $message = new MailTemplateMessage($template, []);

        $message
            ->assertSeeInHtml('Thanks!')
            ->assertSeeInText('Thanks!');
    }

    /**
     * Test simple render with two parameters.
     */
    public function test_with_parameters(): void
    {
        $template = self::makeMailTemplate(<<<'DOC'
        Hello {name},

        I am a test mail.

        Cheers, {sender}
        DOC, ['name', 'sender']);

        $message = new MailTemplateMessage($template, [
            'name' => 'Steve',
            'sender' => 'Jacob',
        ]);

        $message
            ->assertSeeInHtml('Hello Steve')
            ->assertSeeInText('Hello Steve')
            ->assertSeeInHtml('Cheers, Jacob')
            ->assertSeeInText('Cheers, Jacob');
    }

    /**
     * Ensure the default footnote is _replaced_ when a template has it's own footnote.
     */
    public function test_footnote_replacement(): void
    {
        $template = self::makeMailTemplate('Hi!', [
            'name',
        ]);

        (new MailTemplateMessage($template, [
            'name' => 'Sam',
        ]))
            ->assertSeeInHtml('Dit is een automatisch bericht vanuit de website, reageren is niet mogelijk.')
            ->assertSeeInText('Dit is een automatisch bericht vanuit de website, reageren is niet mogelijk.');

        $template->footnote = 'I am a better footnote, featuring {name}!';
        $template->save();

        (new MailTemplateMessage($template, [
            'name' => 'Sam',
        ]))
            ->assertDontSeeInHtml('Dit is een automatisch bericht vanuit de website, reageren is niet mogelijk.')
            ->assertDontSeeInText('Dit is een automatisch bericht vanuit de website, reageren is niet mogelijk.')
            ->assertSeeInHtml('I am a better footnote, featuring Sam!')
            ->assertSeeInText('I am a better footnote, featuring Sam!');
    }

    /**
     * Ensure that a parameter's value is not influential on what values are replaced (no recursive replacements).
     */
    public function test_replacement_with_variable_like_params(): void
    {
        $template = self::makeMailTemplate('{bravo} {alpha}', [
            'alpha',
            'bravo',
        ]);

        (new MailTemplateMessage($template, [
            'alpha' => '{bravo}',
            'bravo' => '{alpha}',
        ]))
            ->assertSeeInHtml('{alpha} {bravo}')
            ->assertSeeInText('{alpha} {bravo}');
    }

    /**
     * Test that HTML is stripped unless it's indicated to be safe.
     */
    public function test_html_in_parameters(): void
    {
        $template = self::makeMailTemplate('Test {data}', ['data']);

        // As user input
        (new MailTemplateMessage($template, ['data' => '<h1>test</h1>']))
            ->assertDontSeeInHtml('<h1>test</h1>')
            ->assertDontSeeInText('<h1>test</h1>');

        // As safe system input
        (new MailTemplateMessage($template, ['data' => new HtmlString('<h1>test</h1>')]))
            ->assertSeeInHtml('<h1>test</h1>')
            ->assertSeeInText('<h1>test</h1>');
    }
}
