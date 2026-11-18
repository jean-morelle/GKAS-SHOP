<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Paramètres de la plateforme (clé / valeur), mis en cache.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'gkas.settings';

    public static function defaults(): array
    {
        return [
            'platform_name' => 'GKAS Shop',
            'platform_logo' => null,
            'company_name' => 'GKAS-GROUP',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_whatsapp' => null,
            'contact_address' => null,
            'trial_days' => (string) config('gkas.trial_days'),
            'currency' => config('gkas.currency'),
            'payment_instructions' => null,
            'terms' => null,
            'privacy' => null,
        ];
    }

    public static function allValues(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return array_merge(self::defaults(), array_filter($stored, fn ($v) => $v !== null && $v !== ''));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allValues()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function trialDays(): int
    {
        return max(1, (int) self::get('trial_days', config('gkas.trial_days')));
    }
}
