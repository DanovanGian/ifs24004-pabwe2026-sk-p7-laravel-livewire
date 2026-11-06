<?php

namespace App\Features\Stock\Models;

use App\Models\User;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    protected static function newFactory(): StockFactory
    {
        return StockFactory::new();
    }

    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'description',
        'quantity',
        'unit',
        'price',
        'min_stock',
        'status',
        'cover_image_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_stock' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
