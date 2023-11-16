<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\FrontmatterParser;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class FrontMatterParserTest extends TestCase
{
    private FrontmatterParser $parser;

    /**
     * @var MockInterface<Logger>
     */
    private MockInterface $logger;

    /**
     * @before
     */
    public function setupParser(): void
    {
        $this->afterApplicationCreated(function () {
            $this->parser = $this->app->make(FrontmatterParser::class);
            $this->logger = Log::partialMock();
        });
    }

    public function test_empty_string(): void
    {
        $input = '';

        $this->assertNull($this->parser->parseHeader($input));
        $this->assertEquals($input, $this->parser->parseBody($input));
    }

    public function test_body_only(): void
    {
        $input = <<<'MARKDOWN'
        # Hello World

        I am an [example][1]. An example is cool.

        [1]: https://example.com
        MARKDOWN;

        $this->assertNull($this->parser->parseHeader($input));
        $this->assertEquals($input, $this->parser->parseBody($input));

        $this->logger->shouldNotHaveBeenCalled();
    }

    public function test_valid_frontmatter(): void
    {
        $body = <<<'MARKDOWN'
        # Hello World

        I am an [example][1]. An example is cool.

        [1]: https://example.com
        MARKDOWN;

        $input = <<<DOC
        ---
        title: Hello World
        description: A hello world is commonly used to introduce a new programming language.
        ---
        {$body}
        DOC;

        $this->assertEquals([
            'title' => 'Hello World',
            'description' => 'A hello world is commonly used to introduce a new programming language.',
        ], $this->parser->parseHeader($input));

        $this->assertEquals($body, $this->parser->parseBody($input));

        $this->logger->shouldNotHaveBeenCalled();
    }

    public function test_empty_frontmatter(): void
    {
        $input = <<<'DOC'
        ---
        ---
        DOC;

        $this->assertEquals([], $this->parser->parseHeader($input));
        $this->assertEquals('', $this->parser->parseBody($input));

        $this->logger->shouldNotHaveBeenCalled();
    }

    public function test_invalid_frontmatter(): void
    {
        $input = <<<'DOC'
        ---
        title: Hello World
        Steve!
        ---
        # Hello World

        I am an [example][1]. An example is cool.

        [1]: https://example.com
        DOC;

        // Error should be thrown twice.
        $this->logger->expects('warning')->twice()->andReturn();

        $this->assertEquals(null, $this->parser->parseHeader($input));
        $this->assertEquals($input, $this->parser->parseBody($input));
    }
}
