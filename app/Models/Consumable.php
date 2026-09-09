<?php

namespace App\Models;

use App\Enums\ConsumableCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumable extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'quantity',
        'min_quantity',
        'unit',
        'location_id',
        'supplier_id',
        'notes',
        'entity_id',
    ];

    protected $appends = ['category_label', 'stock_status', 'stock_label'];

    protected function casts(): array
    {
        return [
            'category' => ConsumableCategory::class,
            'quantity' => 'integer',
            'min_quantity' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category instanceof ConsumableCategory
            ? $this->category->label()
            : (string) $this->category;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'empty';
        }

        if ($this->quantity <= $this->min_quantity) {
            return 'low';
        }

        return 'ok';
    }

    public function getStockLabelAttribute(): string
    {
        return match ($this->stock_status) {
            'empty' => 'Rupture',
            'low' => 'Stock bas',
            default => 'OK',
        };
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_quantity');
    }
}
