<?php

namespace App\Models;

use App\Enums\AccessState;
use App\Enums\StoreStatus;
use App\Enums\StoreTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Champs modifiables par formulaire. Les champs sensibles (statut,
     * dates d'essai et d'abonnement, propriétaire) ne sont jamais assignés
     * en masse : ils passent par les services dédiés.
     */
    protected $fillable = [
        'name', 'description', 'primary_color', 'theme', 'whatsapp', 'phone',
        'email', 'address', 'delivery_zone', 'opening_hours', 'order_conditions',
        'facebook_url', 'instagram_url', 'tiktok_url', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'theme' => StoreTheme::class,
            'is_published' => 'boolean',
            'is_demo' => 'boolean',
            'free_delivery_threshold' => 'decimal:2',
            'suspended_at' => 'datetime',
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_activated_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * {store} accepte un identifiant public (« bella-fashion ») ou, pour les
     * routes servies sur un domaine personnalisé, un nom d'hôte (« bellafashion.com »).
     * Un identifiant ne contient jamais de point : aucune ambiguïté possible.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null && str_contains((string) $value, '.')) {
            return $this->where('custom_domain', mb_strtolower((string) $value))->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    // ---- Relations -------------------------------------------------------

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /** Alias de users() : sert à la liaison « scopée » de {member} dans la gestion d'équipe. */
    public function members(): BelongsToMany
    {
        return $this->users();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function trialHistories(): HasMany
    {
        return $this->hasMany(TrialHistory::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    // ---- État d'accès (calculé en direct, sans dépendre du planificateur) --

    public function hasActiveSubscription(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->subscription_ends_at !== null && $this->subscription_ends_at->greaterThan($at);
    }

    public function isOnTrial(?Carbon $at = null): bool
    {
        $at ??= now();

        return ! $this->hasActiveSubscription($at)
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->greaterThan($at);
    }

    public function accessState(): AccessState
    {
        return match (true) {
            $this->status === StoreStatus::Archived => AccessState::Archived,
            $this->status === StoreStatus::Suspended => AccessState::Suspended,
            $this->hasActiveSubscription() => AccessState::Subscribed,
            $this->isOnTrial() => AccessState::Trial,
            default => AccessState::Expired,
        };
    }

    /** La boutique peut-elle recevoir des commandes et être modifiée ? */
    public function isOperational(): bool
    {
        return $this->accessState()->isOperational();
    }

    /** Date de fin de l'accès en cours (abonnement ou essai). */
    public function accessEndsAt(): ?Carbon
    {
        return $this->hasActiveSubscription() ? $this->subscription_ends_at : $this->trial_ends_at;
    }

    /** Jours restants (arrondi supérieur, 0 si expiré). */
    public function daysRemaining(): int
    {
        $end = $this->accessEndsAt();

        if (! $end || $end->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($end) / 86400);
    }

    public function scopeOperational(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', StoreStatus::Active)
            ->where(fn (Builder $q) => $q->where('subscription_ends_at', '>', $now)->orWhere('trial_ends_at', '>', $now));
    }

    public function scopeOnTrial(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', StoreStatus::Active)
            ->where('trial_ends_at', '>', $now)
            ->where(fn (Builder $q) => $q->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', $now));
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', StoreStatus::Active)->where('subscription_ends_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', StoreStatus::Active)
            ->where(fn (Builder $q) => $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', $now));
    }

    // ---- Présentation ----------------------------------------------------

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function bannerUrl(): ?string
    {
        return $this->banner_path ? Storage::disk('public')->url($this->banner_path) : null;
    }

    /** Adresse à partager : le domaine personnalisé s'il existe, sinon l'adresse sur la plateforme. */
    public function publicUrl(): string
    {
        return $this->customDomainUrl() ?? $this->platformUrl();
    }

    /** Adresse sur la plateforme : APP_URL/{identifiant}. Fonctionne toujours. */
    public function platformUrl(): string
    {
        return route('shop.home', $this);
    }

    public function customDomainUrl(): ?string
    {
        return $this->custom_domain ? config('gkas.custom_domain_scheme').'://'.$this->custom_domain : null;
    }

    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->whatsapp ?: $this->phone));

        return $digits ?: null;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 2));
    }
}
