<?php

namespace App\Services;

use App\Support\Config;

class OpenAIException extends \RuntimeException
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}

class OpenAIClient
{
    public function classify(string $systemPrompt, string $userPrompt): array
    {
        $apiKey = Config::get('openai.api_key');
        if (!$apiKey || $apiKey === 'PASTE_YOUR_KEY_HERE') {
            throw new \RuntimeException('OpenAI API klíč není nastaven v config/app.php.');
        }

        $baseUrl = rtrim(Config::get('openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) Config::get('openai.request_timeout', 30);
        $model = Config::get('openai.model', 'gpt-5.0-mini');

        $payload = [
            'model' => $model,
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $ch = curl_init($baseUrl.'/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Chyba komunikace s OpenAI API: '.$error);
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $message = $decoded['error']['message'] ?? 'Neznámá chyba OpenAI ('.$httpCode.')';
            throw new OpenAIException($message, $httpCode);
        }

        $choice = $decoded['choices'][0]['message']['content'] ?? '';
        $usage = $decoded['usage'] ?? [];

        return [
            'content' => $choice,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
            ],
            'raw' => $decoded,
        ];
    }
}
