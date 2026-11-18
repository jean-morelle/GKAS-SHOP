<?php

namespace App\Http\Requests\Admin;

use App\Enums\StoreTheme;
use App\Enums\UserRole;
use App\Http\Requests\Vendor\StoreAppearanceRequest;
use App\Http\Requests\Vendor\StoreSettingsRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Création / modification d'une boutique depuis l'espace GKAS. */
class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        return $store ? $this->user()->can('update', $store) : $this->user()->can('create', \App\Models\Store::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? mb_strtolower(trim((string) $this->input('slug'))) : null,
            'is_published' => $this->boolean('is_published'),
            'is_demo' => $this->boolean('is_demo'),
        ]);
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $creating = $store === null;

        $rules = StoreSettingsRequest::baseRules() + StoreAppearanceRequest::brandingRules() + [
            'slug' => [
                $creating ? 'nullable' : 'required', 'string', 'min:3', 'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(config('gkas.reserved_slugs')),
                Rule::unique('stores', 'slug')->ignore($store?->id),
            ],
            'theme' => ['required', Rule::enum(StoreTheme::class)],
            'is_published' => ['boolean'],
            'is_demo' => ['boolean'],
        ];

        if ($creating) {
            $rules += [
                'owner_mode' => ['required', 'in:existing,new'],
                'owner_id' => ['required_if:owner_mode,existing', 'nullable', 'integer',
                    Rule::exists('users', 'id')->where('role', UserRole::Vendor->value)->whereNull('deleted_at')],
                'owner_name' => ['required_if:owner_mode,new', 'nullable', 'string', 'max:120'],
                'owner_email' => ['required_if:owner_mode,new', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
                'owner_phone' => ['nullable', 'string', 'max:30'],
                'prospect_id' => ['nullable', 'integer', Rule::exists('demo_requests', 'id')],
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return (new StoreSettingsRequest)->attributes() + [
            'slug' => 'identifiant public',
            'owner_id' => 'vendeur',
            'owner_name' => 'nom du vendeur',
            'owner_email' => 'e-mail du vendeur',
            'primary_color' => 'couleur principale',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'L\'identifiant public ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug.not_in' => 'Cet identifiant est réservé par la plateforme.',
            'slug.unique' => 'Cet identifiant est déjà utilisé par une autre boutique.',
        ];
    }

    /** Champs de la boutique assignables en masse. */
    public function storeData(): array
    {
        return $this->safe()->only([
            'name', 'description', 'primary_color', 'theme', 'whatsapp', 'phone', 'email', 'address',
            'delivery_zone', 'opening_hours', 'order_conditions', 'facebook_url', 'instagram_url',
            'tiktok_url', 'is_published',
        ]);
    }
}
