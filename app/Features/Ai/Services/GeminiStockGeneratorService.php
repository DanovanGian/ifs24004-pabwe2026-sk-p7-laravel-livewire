<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Models\Stock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class GeminiStockGeneratorService implements AiClientInterface
{
    public function generateStockItems(string $prompt, int $count): array
    {
        $apiKey = (string) config('gemini.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di file .env.');
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'sku' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer', 'minimum' => 0, 'maximum' => Stock::MAX_QUANTITY],
                            'unit' => ['type' => 'string'],
                            'price' => ['type' => 'number', 'minimum' => 0, 'maximum' => 9999999999999.99],
                            'min_stock' => ['type' => 'integer', 'minimum' => 0, 'maximum' => Stock::MAX_QUANTITY],
                        ],
                        'required' => ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
                    ],
                ],
            ],
            'required' => ['items'],
        ];

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])
            ->acceptJson()
            ->timeout((int) config('gemini.timeout', 30))
            ->retry(3, 250, fn ($exception) =>
                $exception instanceof RequestException
                && $exception->response->status() === 503,
                false,
            )
            ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                'model' => config('gemini.model', 'gemini-3.8-flash'),
                'system_instruction' => 'Buat daftar item inventaris yang realistis dalam Bahasa Indonesia. Gunakan konteks dari pengguna. Hasilkan tepat jumlah item yang diminta. Isi SKU dan deskripsi dengan string kosong jika tidak relevan. Berikan perkiraan harga dalam Rupiah sebagai angka. Jangan mengarang informasi sensitif.',
                'input' => "Buat tepat {$count} item stok untuk konteks berikut: ".trim($prompt),
                'response_format' => [
                    'type' => 'text',
                    'mime_type' => 'application/json',
                    'schema' => $schema,
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error', []);
            $providerStatus = is_array($error) && is_string($error['status'] ?? null)
                ? $error['status']
                : null;

            Log::warning('Gemini stock generation failed', [
                'http_status' => $response->status(),
                'provider_status' => $providerStatus,
                'request_id' => $response->header('x-goog-request-id'),
            ]);

            throw new RuntimeException($this->failureMessage($response->status(), $providerStatus));
        }

        $outputText = collect($response->json('steps', []))
            ->flatMap(fn (array $step) => $step['content'] ?? [])
            ->firstWhere('type', 'text');
        $decoded = is_string($outputText['text'] ?? null)
            ? json_decode($outputText['text'], true)
            : null;

        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            throw new RuntimeException('Respons Gemini tidak memiliki format daftar barang yang valid.');
        }

        return collect($decoded['items'])
            ->take($count)
            ->map(fn (array $item) => GeneratedStockItem::fromArray($item)->toStockData())
            ->values()
            ->all();
    }

    private function failureMessage(int $httpStatus, ?string $providerStatus): string
    {
        if (in_array($providerStatus, ['API_KEY_INVALID', 'UNAUTHENTICATED'], true)) {
            return 'API key Gemini tidak valid. Periksa GEMINI_API_KEY di file .env, lalu jalankan php artisan config:clear.';
        }

        if ($httpStatus === 429 || $providerStatus === 'RESOURCE_EXHAUSTED') {
            return 'Kuota atau batas penggunaan Gemini API tercapai. Periksa Usage dan Limits di Google AI Studio atau Google Cloud project.';
        }

        if ($httpStatus === 403 || $providerStatus === 'PERMISSION_DENIED') {
            return 'Proyek atau API key Google tidak memiliki izin. Pastikan Gemini API aktif dan pembatasan API key mengizinkan Gemini.';
        }

        if ($httpStatus === 404 || $providerStatus === 'NOT_FOUND') {
            return 'Model Gemini tidak ditemukan. Periksa GEMINI_MODEL di file .env.';
        }

        if ($httpStatus >= 500) {
            return 'Layanan Gemini sedang mengalami gangguan. Coba lagi beberapa saat nanti.';
        }

        return 'Permintaan Gemini ditolak. Periksa kode error di storage/logs/laravel.log.';
    }
}
