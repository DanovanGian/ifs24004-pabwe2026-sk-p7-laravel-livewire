<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generateStockItems(string $prompt, int $count): array
    {
        $apiKey = (string) config('openai.api_key');
        $endpoint = rtrim((string) config('openai.endpoint'), '/');
        $model = (string) config('openai.model');

        if ($apiKey === '' || $endpoint === '' || $model === '') {
            throw new RuntimeException('Lengkapi OPENAI_ENDPOINT, OPENAI_API_KEY, dan OPENAI_MODEL di file .env.');
        }

        $url = str_ends_with($endpoint, '/chat/completions')
            ? $endpoint
            : $endpoint.'/chat/completions';

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('openai.timeout', 30))
            ->post($url, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Buat data inventaris realistis dalam Bahasa Indonesia. Jawab hanya JSON berbentuk {"items":[...]}. Buat tepat sebanyak jumlah yang diminta. Setiap item wajib berisi name (string), sku (string atau null), description (string atau null), quantity (integer >= 0), unit (string), price (angka atau null), dan min_stock (integer >= 0). Harga dalam Rupiah. Jangan mengarang informasi sensitif.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Buat tepat {$count} item stok untuk konteks berikut: ".trim($prompt),
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            Log::warning('OpenAI compatible stock generation failed', [
                'http_status' => $response->status(),
                'request_id' => $response->header('x-request-id'),
            ]);

            throw new RuntimeException($this->failureMessage($response->status()));
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            throw new RuntimeException('Respons AI tidak memiliki format daftar barang yang valid.');
        }

        return collect($decoded['items'])
            ->filter(fn ($item) => is_array($item))
            ->take($count)
            ->map(fn (array $item) => GeneratedStockItem::fromArray($item)->toStockData())
            ->values()
            ->all();
    }

    private function failureMessage(int $httpStatus): string
    {
        return match ($httpStatus) {
            401, 403 => 'OpenAI menolak API key atau akses. Periksa OPENAI_API_KEY dan izin akun.',
            404 => 'Endpoint atau model tidak ditemukan. Periksa OPENAI_ENDPOINT dan OPENAI_MODEL.',
            429 => 'Batas penggunaan OpenAI tercapai. Periksa kuota atau billing akun API.',
            default => $httpStatus >= 500
                ? 'Layanan AI sedang mengalami gangguan. Coba lagi beberapa saat nanti.'
                : 'Permintaan ke layanan AI ditolak. Periksa endpoint, model, dan konfigurasi API.',
        };
    }
}
