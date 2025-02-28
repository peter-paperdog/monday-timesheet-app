<?php

namespace App\Services;

use App\Classes\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.slack.notifications.bot_user_oauth_token');

        if (empty($this->token)) {
            Log::error('Slack bot user OAuth token is not set.');
            throw new \InvalidArgumentException('Slack bot token is missing in the configuration.');
        }
    }

    /**
     * Privát üzenet küldése egy adott felhasználónak.
     * @throws ConnectionException
     */
    public function sendPrivateMessage(string $userId, string $message): array
    {
        $response = Http::withToken($this->token)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $userId,
                'text' => $message,
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Felhasználók listájának lekérése (Slack user ID-k megszerzése).
     * @throws ConnectionException
     */
    public function getUserList(): array
    {
        $response = Http::withToken($this->token)
            ->get('https://slack.com/api/users.list');

        return $this->handleResponse($response);
    }

    /**
     * Egy adott felhasználó adatainak lekérése Slack ID alapján.
     * @throws ConnectionException
     */
    public function getUserInfo(string $userId): array
    {
        $response = Http::withToken($this->token)
            ->get('https://slack.com/api/users.info', [
                'user' => $userId,
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Egy adott felhasználó adatainak lekérése Slack ID alapján.
     * @throws ConnectionException
     */
    public function getUserByEmail(string $email): array
    {
        $response = Http::withToken($this->token)
            ->get('https://slack.com/api/users.lookupByEmail', [
                'email' => $email,
            ]);

        return $this->handleResponse($response);
    }

    public function sendInteractiveMessage(string $userId, string $question, array $options): array
    {
        // Gombok összeállítása
        $actions = [];
        foreach ($options as $index => $option) {
            $cleanValue = preg_replace('/[^\p{L}\p{N}_]+/u', '', $option);
            $actions[] = [
                'type' => 'button',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $option
                ],
                'value' => str_replace(' ', '_', $cleanValue)
            ];
        }

        // Slack üzenet formázása Block Kit segítségével
        $payload = [
            'channel' => $userId,
            'text' => $question,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*$question*"
                    ]
                ],
                [
                    'type' => 'actions',
                    'elements' => $actions
                ]
            ]
        ];

        $response = Http::withToken($this->token)
            ->post('https://slack.com/api/chat.postMessage', $payload);

        return $this->handleResponse($response);
    }

    public function updateSlackMessage(string $responseUrl, string $status): void
    {
        $statusEmojis = [
            'office' => '🏢 Office',
            'wfh' => '🏠 WFH',
            'off' => '🛑 Off',
            'sick' => '🤒 Sick'
        ];

        $selectedStatus = $statusEmojis[$status] ?? $status;

        $message = "✅ A státuszod mostantól: *{$selectedStatus}*";

        // Slack API hívás az üzenet frissítésére
        \Illuminate\Support\Facades\Http::post($responseUrl, [
            'replace_original' => true, // Eredeti üzenet módosítása
            'text' => $message
        ]);
    }

    public function getRateLimitStatus(): array
    {
        $response = Http::withToken($this->token)
            ->get('https://slack.com/api/auth.test');

        $resetTimestamp = $response->header('X-RateLimit-Reset');
        $resetTime = $resetTimestamp ? date('Y-m-d H:i:s', $resetTimestamp) : null;

        return [
            'limit' => $response->header('X-RateLimit-Limit') ?? 'Unknown',
            'remaining' => $response->header('X-RateLimit-Remaining') ?? 'Unknown',
            'reset_time' => $resetTime ?? 'Unknown',
        ];
    }

    protected function handleResponse($response): array
    {
        $result = $response->json();

        if (!$response->successful() || !$result['ok']) {
            $error = $result['error'] ?? 'Unknown error';
            Log::error("Slack API Error: {$error}");

            return [
                'success' => false,
                'error' => $error,
                'response' => $result
            ];
        }

        return [
            'success' => true,
            'response' => $result
        ];
    }

}
