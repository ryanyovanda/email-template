<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over DeepSeek's OpenAI-compatible chat completions endpoint.
 */
class DeepSeekClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.deepseek.key'));
    }

    public function model(): string
    {
        return (string) config('services.deepseek.model');
    }

    /**
     * Ask for a single JSON object back.
     *
     * @return array{content: array<string, mixed>, usage: array<string, int>}
     */
    public function json(string $systemPrompt, string $userPrompt, float $temperature = 0.7): array
    {
        if (! $this->isConfigured()) {
            throw new AiException('The AI service is not configured yet. Add DEEPSEEK_API_KEY to your environment.');
        }

        $response = Http::withToken((string) config('services.deepseek.key'))
            ->timeout((int) config('services.deepseek.timeout'))
            ->retry(2, 1000, throw: false)
            ->post(rtrim((string) config('services.deepseek.base_url'), '/').'/chat/completions', [
                'model' => $this->model(),
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if ($response->failed()) {
            throw new AiException($this->readableError($response->status(), $response->json('error.message')));
        }

        $content = (string) $response->json('choices.0.message.content');
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new AiException('The AI returned a response we could not read. Please try again.');
        }

        return [
            'content' => $decoded,
            'usage' => [
                'prompt_tokens' => (int) $response->json('usage.prompt_tokens', 0),
                'completion_tokens' => (int) $response->json('usage.completion_tokens', 0),
                'total_tokens' => (int) $response->json('usage.total_tokens', 0),
            ],
        ];
    }

    private function readableError(int $status, ?string $message): string
    {
        return match ($status) {
            401 => 'The AI service rejected our credentials. Please contact support.',
            402 => 'The AI service account is out of credit. Please contact support.',
            429 => 'The AI service is rate limiting us right now. Please try again in a moment.',
            default => $message ? "The AI service failed: {$message}" : 'The AI service is unavailable right now. Please try again.',
        };
    }
}
