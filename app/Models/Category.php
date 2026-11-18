<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Inclut les produits supprimés logiquement : ils bloquent aussi la suppression. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isUsed(): bool
    {
        return $this->products()->withTrashed()->exists();
    }
}
