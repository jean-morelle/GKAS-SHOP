<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /** Aucune assignation en masse : les commandes sont créées par OrderService. */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /** Commandes prises en compte dans le chiffre d'affaires : livrées ou marquées payées, hors annulées. */
    public function scopeCountedAsSale(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled)
            ->where(fn (Builder $q) => $q->where('status', OrderStatus::Delivered)->orWhereNotNull('paid_at'));
    }

    public function whatsappCustomerNumber(): string
    {
        return preg_replace('/\D+/', '', $this->customer_phone);
    }
}
