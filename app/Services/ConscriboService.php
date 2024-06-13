<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ConscriboService
{
    private readonly string $url;

    private readonly string $username;

    private readonly string $password;

    private ?string $sessionId = null;

    private bool $loggedIn = false;

    public function __construct(Repository $config)
    {
        $username = $config->get('services.conscribo.username');
        $password = $config->get('services.conscribo.password');

        assert(! empty($username), 'Conscribo username is not set');
        assert(! empty($password), 'Conscribo password is not set');

        $this->username = $username;
        $this->password = $password;

        $url = $config->get('services.conscribo.url');
        if (empty($url)) {
            $account = $config->get('services.conscribo.account');

            assert(! empty($account), 'Conscribo account is not set');

            $url = "https://secure.conscribo.nl/{$account}/request.json";
        }

        assert(filter_var($url, FILTER_VALIDATE_URL), 'Invalid Conscribo URL');

        $this->url = $url;
    }

    public function call(string $command, array $arguments = []): array
    {
        if (Arr::hasAny($arguments, ['requestSequence', 'command'])) {
            Log::warning('Got invalid request for {command} with arguments {arguments}', [
                'command' => $command,
                'arguments' => $arguments,
            ]);

            throw new RuntimeException('Request args should not contain requestSequence or command keys');
        }

        // Prep main request
        $mainRequest = array_merge([
            'requestSequence' => 'main',
            'command' => $command,
        ], $arguments);

        // Prep login request
        $loginRequest = [
            'requestSequence' => 'login',
            'command' => 'authenticateWithUserAndPass',
            'userName' => $this->username,
            'passPhrase' => $this->password,
        ];

        // Join requests, omit login if already logged in
        $requestBody = [
            'requests' => [
                'request' => $this->loggedIn
                    ? [$mainRequest]
                    : [$loginRequest, $mainRequest],
            ],
        ];

        Log::debug('Sending main request {request}', ['request' => $mainRequest]);

        $response = Http::asJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Conscribo-API-Version' => '0.20161212',
                'X-Conscribo-Client' => 'Gumbo Directory on Laravel',
            ])
            ->when($this->loggedIn, fn ($http) => $http->withHeader('X-Conscribo-SessionId', $this->sessionId))
            ->post($this->url, $requestBody);

        Log::debug('Received response {response}', [
            'status' => $response->status(),
            'reason' => $response->reason(),
            'body' => $response->body(),
        ]);

        // Even errors return 200
        if (! $response->ok()) {
            throw new RuntimeException("Conscribo API call failed. Expected 200 OK, but got {$response->status()}");
        }

        // Check for a complete request failure
        $singleResponse = $response->json('result');
        if ($singleResponse != null) {
            throw new RuntimeException("Conscribo API call failed: {$this->parseError($singleResponse)}");
        }

        // Get the two response items from the body
        $responseBody = $response->json('results.result');
        assert(is_array($responseBody), 'Response body is not an array');

        if (! $this->loggedIn) {
            $loginResponse = Arr::first($responseBody, fn ($row) => $row['requestSequence'] === 'login');
            assert(is_array($loginResponse), 'Login response is not an array');

            // Check if login was successful
            if (Arr::get($loginResponse, 'success') !== 1) {
                throw new RuntimeException("Conscribo login failed: {$this->parseError($loginResponse)}");
            }

            // Fetch session ID
            $this->sessionId = Arr::get($loginResponse, 'sessionId');
            assert(is_string($this->sessionId), 'Session ID is not a string');

            Log::debug('Logged in with sessionID {sessionId}', ['sessionId' => $this->sessionId]);

            $this->loggedIn = true;
        }

        $mainResponse = Arr::first($responseBody, fn ($row) => $row['requestSequence'] === 'main');
        assert(is_array($mainResponse), 'Main response is not an array');

        // Check if main command was successful
        if (Arr::get($mainResponse, 'success') !== 1) {
            throw new RuntimeException("Conscribo command {$command} failed: {$this->parseError($mainResponse)}");
        }

        return Arr::except($mainResponse, ['status', 'requestSequence']);
    }

    public function getIsLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    private function parseError(array $loginResponse): string
    {
        return Arr::get($loginResponse, 'notifications.notification.0')
            ?? 'Unknown error';
    }
}
