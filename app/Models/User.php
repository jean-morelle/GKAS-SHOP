<?php

namespace App\Models;

use App\Enums\StoreRole;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /** @var array<int, ?StoreRole> */
    protected array $storeRoleCache = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)->withPivot('role')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSales(): bool
    {
        return $this->role === UserRole::Sales;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isSales();
    }

    public function isVendor(): bool
    {
        return $this->role === UserRole::Vendor;
    }

    public function belongsToStore(Store $store): bool
    {
        return $this->storeRole($store) !== null;
    }

    /** Rôle dans une boutique (mémorisé pour la durée de la requête). */
    public function storeRole(Store|int $store): ?StoreRole
    {
        $id = $store instanceof Store ? $store->getKey() : $store;

        if (! array_key_exists($id, $this->storeRoleCache)) {
            $role = $this->stores()->whereKey($id)->first()?->pivot->role;
            $this->storeRoleCache[$id] = $role ? StoreRole::tryFrom($role) : null;
        }

        return $this->storeRoleCache[$id];
    }

    /** Droit dans une boutique : orders, catalog, settings, stats, export, billing, team. */
    public function canInStore(Store|int $store, string $ability): bool
    {
        return $this->isVendor() && $this->is_active && (bool) $this->storeRole($store)?->can($ability);
    }

    public function forgetStoreRoles(): void
    {
        $this->storeRoleCache = [];
    }

    public function homeRoute(): string
    {
        if ($this->isStaff()) {
            return route('admin.dashboard');
        }

        $store = $this->stores()->orderBy('stores.name')->first();

        return $store ? route('vendor.dashboard', $store) : route('account.edit');
    }
}
