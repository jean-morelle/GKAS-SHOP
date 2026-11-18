<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'description', 'price', 'sale_price',
        'stock_quantity', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasDiscount(): bool
    {
        return $this->sale_price !== null && bccomp((string) $this->sale_price, (string) $this->price, 2) < 0;
    }

    /** Prix effectivement facturé (chaîne décimale, jamais un float). */
    public function effectivePrice(): string
    {
        return $this->hasDiscount() ? (string) $this->sale_price : (string) $this->price;
    }

    public function tracksStock(): bool
    {
        return $this->stock_quantity !== null;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && (! $this->tracksStock() || $this->stock_quantity > 0);
    }

    public function imageUrl(): ?string
    {
        return $this->mainImage?->url();
    }
}
