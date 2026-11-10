<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Models\Stock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GroqStockGeneratorService implements AiClientInterface
{
    public function generateStockItems(string $prompt, int $count): array
    {
        $apiKey = (string) config('groq.api_key');
        $model = (string) config('groq.model');

        if ($apiKey === '') {
            throw new RuntimeException('GROQ_API_KEY belum diatur di file .env.');
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'sku' => ['type' => ['string', 'null']],
                            'description' => ['type' => ['string', 'null']],
                            'quantity' => ['type' => 'integer'],
                            'unit' => ['type' => 'string'],
                            'price' => ['type' => ['number', 'null']],
                            'min_stock' => ['type' => 'integer'],
                        ],
                        'required' => ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
                    ],
                ],
            ],
            'required' => ['items'],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('groq.timeout', 30))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Buat daftar barang inventaris realistis dalam Bahasa Indonesia. Kembalikan tepat jumlah item yang diminta dalam JSON sesuai skema. Isi SKU atau deskripsi dengan string kosong jika tidak relevan. Harga perkiraan dalam Rupiah. Jangan mengarang informasi sensitif.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Buat tepat {$count} item stok untuk konteks berikut: ".trim($prompt),
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'stock_items',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
            ]);

        if ($response->failed()) {
            $errorCode = $response->json('error.code');
            Log::warning('Groq stock generation failed', [
                'http_status' => $response->status(),
                'provider_code' => is_string($errorCode) ? $errorCode : null,
                'request_id' => $response->header('x-request-id'),
            ]);

            throw new RuntimeException($this->failureMessage($response->status()));
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            throw new RuntimeException('Respons Groq tidak memiliki format daftar barang yang valid.');
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
            401, 403 => 'Groq menolak API key. Periksa GROQ_API_KEY di file .env.',
            404 => 'Model Groq tidak ditemukan. Periksa GROQ_MODEL di file .env.',
            429 => 'Batas gratis Groq tercapai. Coba lagi setelah batas penggunaan direset.',
            default => $httpStatus >= 500
                ? 'Layanan Groq sedang sibuk. Coba lagi beberapa saat nanti.'
                : 'Permintaan ke Groq ditolak. Periksa konfigurasi model dan API key.',
        };
    }
}
