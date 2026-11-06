<?php

namespace App\Features\Ai\DTO;

use App\Features\Stock\Enums\StockStatus;

final readonly class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku,
        public ?string $description,
        public int $quantity,
        public string $unit,
        public ?float $price,
        public int $minStock,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            sku: filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
            description: filled($data['description'] ?? null) ? (string) $data['description'] : null,
            quantity: max(0, (int) ($data['quantity'] ?? 0)),
            unit: filled($data['unit'] ?? null) ? trim((string) $data['unit']) : 'pcs',
            price: filled($data['price'] ?? null) ? (float) $data['price'] : null,
            minStock: max(0, (int) ($data['min_stock'] ?? 5)),
        );
    }

    public function toStockData(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
            'status' => StockStatus::fromQuantity($this->quantity, $this->minStock)->value,
        ];
    }
}
