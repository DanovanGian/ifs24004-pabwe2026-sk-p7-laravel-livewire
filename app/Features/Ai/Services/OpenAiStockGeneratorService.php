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
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key belum diatur di file .env.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('openai.timeout', 30))
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('openai.model', 'gpt-4.1-mini'),
                'input' => [
                    ['role' => 'system', 'content' => 'Buat daftar item inventaris yang realistis. Gunakan data sesuai permintaan pengguna. Kembalikan hanya JSON sesuai skema. Jangan mengarang informasi sensitif.'],
                    ['role' => 'user', 'content' => "Buat tepat {$count} item stok untuk konteks berikut: ".trim($prompt)],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'stock_items',
                        'strict' => true,
                        'schema' => [
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
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('OpenAI stock generation failed', ['status' => $response->status()]);
            throw new RuntimeException('Layanan AI gagal membuat daftar barang. Coba lagi nanti.');
        }

        $outputText = collect($response->json('output', []))
            ->flatMap(fn (array $output) => $output['content'] ?? [])
            ->firstWhere('type', 'output_text');
        $decoded = is_string($outputText['text'] ?? null)
            ? json_decode($outputText['text'], true)
            : null;

        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            throw new RuntimeException('Respons AI tidak memiliki format daftar barang yang valid.');
        }

        return collect($decoded['items'])
            ->take($count)
            ->map(fn (array $item) => GeneratedStockItem::fromArray($item)->toStockData())
            ->values()
            ->all();
    }
}
