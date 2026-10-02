<?php

namespace App\Services\Assistant;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Thin HTTP client for the OpenAI-compatible endpoints.
 *
 * Every method degrades instead of throwing: a widget that takes the home page
 * down because an API is slow or rate limited is worse than a widget that says
 * "je n'ai pas reussi".
 */
class OpenAiClient
{
    public function isConfigured(): bool
    {
        return filled(config('assistant.key'))
            && (string) config('assistant.driver') !== 'rules';
    }

    /** The driver actually in use, once the configuration is resolved. */
    public function driver(): string
    {
        if (config('assistant.driver') === 'rules') {
            return 'rules';
        }

        return $this->isConfigured() ? 'openai' : 'rules';
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>|null  $tools
     * @return array<string, mixed>|null
     */
    public function chatCompletion(array $messages, ?array $tools = null): ?array
    {
        $payload = [
            'model' => config('assistant.model', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => 0.3,
        ];

        if ($tools) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->post('/chat/completions', $payload);

        if ($response === null) {
            return null;
        }

        return $response['choices'][0]['message'] ?? null;
    }

    /**
     * Returns the raw assistant message; the caller decodes the JSON itself,
     * because the model is free to wrap it in prose.
     */
    public function visionCompletion(string $model, string $instructions, UploadedFile|string $image): ?string
    {
        $dataUrl = $image instanceof UploadedFile ? $this->dataUrl($image) : $this->readableUrl($image);

        if ($dataUrl === null) {
            return null;
        }

        $response = $this->post('/chat/completions', [
            'model' => $model,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $instructions],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                ],
            ]],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? null;

        return is_string($content) ? $content : null;
    }

    /**
     * Turns a stored photo into something the vision API can actually read.
     * A public URL we host is downloaded and inlined; a remote URL is passed
     * through untouched.
     */
    private function readableUrl(string $image): ?string
    {
        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, 'data:')) {
            return $image;
        }

        if (preg_match('#^https?://#i', $image)) {
            return Str::startsWith($image, [rtrim(config('app.url'), '/'), 'localhost', '127.0.0.1'])
                ? $this->dataUrlFromPath($this->localPathOf($image))
                : $image;
        }

        return $this->dataUrlFromPath(Storage::disk('public')->path($image));
    }

    /** Maps a URL served by this application back to a file on disk. */
    private function localPathOf(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $prefix = '/storage/';

        if (! str_contains($path, $prefix)) {
            return null;
        }

        return Storage::disk('public')->path(substr($path, strpos($path, $prefix) + strlen($prefix)));
    }

    private function dataUrlFromPath(?string $path): ?string
    {
        if ($path === null || ! is_readable($path)) {
            return null;
        }

        $mime = (new UploadedFile($path, basename($path), null, null, true))->getMimeType() ?: 'image/jpeg';

        return sprintf('data:%s;base64,%s', $mime, base64_encode((string) file_get_contents($path)));
    }

    /** @return array<string, mixed>|null */
    private function post(string $path, array $payload): ?array
    {
        try {
            $response = Http::withToken((string) config('assistant.key'))
                ->baseUrl((string) config('assistant.api_base', 'https://api.openai.com/v1'))
                ->timeout((int) config('assistant.timeout', 25))
                ->acceptJson()
                ->asJson()
                ->post($path, $payload);

            if ($response->failed()) {
                Log::warning('Assistant API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Assistant API unreachable', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Models answer with prose around the JSON often enough that stripping the
     * fences and any prefix is cheaper than a second round-trip.
     *
     * @return array<string, mixed>|null
     */
    public function decodeJson(?string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $raw) ?? $raw;

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function dataUrl(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return null;
        }

        $mime = $file->getMimeType() ?: 'image/jpeg';

        return sprintf('data:%s;base64,%s', $mime, base64_encode((string) file_get_contents($path)));
    }
}
