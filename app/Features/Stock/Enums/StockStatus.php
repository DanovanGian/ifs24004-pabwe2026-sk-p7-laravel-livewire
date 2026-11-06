<?php

namespace App\Features\Stock\Enums;

enum StockStatus: string
{
    case AVAILABLE = 'available';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::LOW_STOCK => 'Stok Menipis',
            self::OUT_OF_STOCK => 'Habis',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-green-100 text-green-700',
            self::LOW_STOCK => 'bg-yellow-100 text-yellow-700',
            self::OUT_OF_STOCK => 'bg-red-100 text-red-700',
        };
    }

    public static function fromQuantity(
        int $quantity,
        int $minStock
    ): self {
        if ($quantity <= 0) {
            return self::OUT_OF_STOCK;
        }

        if ($quantity <= $minStock) {
            return self::LOW_STOCK;
        }

        return self::AVAILABLE;
    }
}

