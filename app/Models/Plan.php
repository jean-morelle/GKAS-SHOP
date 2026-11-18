<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'price', 'duration_days', 'features', 'is_active', 'position'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('price');
    }

    public function durationLabel(): string
    {
        return match (true) {
            $this->duration_days % 365 === 0 => ($this->duration_days / 365).' an'.($this->duration_days > 365 ? 's' : ''),
            $this->duration_days % 30 === 0 => ($this->duration_days / 30).' mois',
            default => $this->duration_days.' jours',
        };
    }
}
