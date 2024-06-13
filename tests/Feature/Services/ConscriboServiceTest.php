<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\ConscriboService;
use AssertionError;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\RequiresSetting;
use RuntimeException;
use Tests\TestCase;

class ConscriboServiceTest extends TestCase
{
    private const TEST_SESSION_ID = 'TestSessionId';

    /**
     * Provides configs that are invalid.
     */
    public static function provide_constructor_missing_config_cases(): iterable
    {
        return [
            'empty' => ['Conscribo username is not set', []],
            'nothing set' => ['Conscribo username is not set', [
                'account' => '',
                'username' => '',
                'password' => '',
            ]],
            'no password' => ['Conscribo password is not set', [
                'account' => 'test',
                'username' => 'username',
                'password' => '',
            ]],

            'no account' => ['Conscribo account is not set', [
                'account' => '',
                'username' => 'username',
                'password' => 'password',
            ]],

            'invalid URL' => ['Invalid Conscribo URL', [
                'account' => 'test',
                'username' => 'username',
                'password' => 'password',
                'url' => 'invalid',
            ]],
        ];
    }

    /**
     * Test happy path.
     */
    public function test_constructor(): void
    {
        $store = new Repository([
            'services' => [
                'conscribo' => [
                    'account' => 'test',
                    'username' => 'username',
                    'password' => 'password',
                ],
            ],
        ]);

        $this->assertInstanceOf(ConscriboService::class, new ConscriboService($store));
    }

    /**
     * @dataProvider provide_constructor_missing_config_cases
     */
    #[RequiresSetting('zend.assertions', '1')]
    public function test_constructor_missing_config(string $expected, array $config): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage($expected);
        new ConscriboService(new Repository([
            'services' => ['conscribo' => $config],
        ]));
    }

    public function test_happy_trail(): void
    {
        Http::fake([
            'https://test.com' => $this->buildMainResponse(1, [
                'data' => 'yes',
            ]),
        ]);

        $response = $this->getConfiguredClient()->call('test');
        $this->assertIsArray($response);

        $this->assertArrayHasKey('success', $response);
        $this->assertEquals(1, $response['success']);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('yes', $response['data']);
    }

    public function test_failed_login(): void
    {
        Http::fake([
            'https://test.com' => $this->buildLoginResponse(0, 'I am a test'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Conscribo login failed: I am a test');
        $this->getConfiguredClient()->call('test');
    }

    public function test_failed_command(): void
    {
        Http::fake([
            'https://test.com' => $this->buildMainResponse(0, 'I am a test'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Conscribo command test failed: I am a test');
        $this->getConfiguredClient()->call('test');
    }

    public function test_non200_response(): void
    {
        Http::fake([
            'https://test.com' => Http::response('', 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Conscribo API call failed');
        $this->getConfiguredClient()->call('test');
    }

    public function test_session_reuse(): void
    {
        Http::fake([
            'https://test.com' => Http::sequence([
                $this->buildMainResponse(1, ['data' => 'yes']),
                $this->buildMainResponse(1, ['data' => 'yes']),
            ]),
        ]);

        $client = $this->getConfiguredClient();

        $this->assertFalse($client->getIsLoggedIn());

        $response = $client->call('test');
        $this->assertIsArray($response);

        $this->assertTrue($client->getIsLoggedIn());

        $response = $client->call('test');
        $this->assertIsArray($response);

        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Conscribo-SessionId', self::TEST_SESSION_ID));
    }

    private function getConfiguredClient(): ConscriboService
    {
        return new ConscriboService(new Repository([
            'services' => [
                'conscribo' => [
                    'url' => 'https://test.com',
                    'account' => 'test',
                    'username' => 'username',
                    'password' => 'password',
                ],
            ],
        ]));
    }

    private function buildResponseArray(string $sequence, int $success, string|array|null $messageOrBody = null)
    {
        $base = [
            'requestSequence' => $sequence,
            'success' => $success,
        ];

        if ($success) {
            return array_merge($messageOrBody ?? [
                'sessionId' => self::TEST_SESSION_ID,
            ], $base);
        }

        $messageOrBody ??= 'Test error';

        return array_merge($base, [
            'notifications' => [
                'notification' => [$messageOrBody, $messageOrBody],
            ],
        ]);
    }

    private function buildLoginResponse(int $success, string $message = 'Test error')
    {
        return Http::response([
            'results' => [
                'result' => [
                    $this->buildResponseArray('login', $success, $message),
                    $this->buildResponseArray('main', 1),
                ],
            ],
        ]);
    }

    private function buildMainResponse(int $success, array|string $responseOrMessage)
    {
        return Http::response([
            'results' => [
                'result' => [
                    $this->buildResponseArray('login', 1),
                    $this->buildResponseArray('main', $success, $responseOrMessage),
                ],
            ],
        ]);
    }
}
