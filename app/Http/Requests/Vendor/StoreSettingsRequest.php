<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

/** Informations générales d'une boutique (vendeur ou administrateur). */
class StoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        return $store && $this->user()->can('manageSettings', $store);
    }

    public function rules(): array
    {
        return self::baseRules();
    }

    public static function baseRules(): array
    {
        $phone = ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().-]{8,30}$/'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'whatsapp' => $phone,
            'phone' => $phone,
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'delivery_zone' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'order_conditions' => ['nullable', 'string', 'max:2000'],
            'facebook_url' => ['nullable', 'url:https,http', 'max:255'],
            'instagram_url' => ['nullable', 'url:https,http', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https,http', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom commercial',
            'whatsapp' => 'numéro WhatsApp',
            'phone' => 'téléphone',
            'delivery_zone' => 'zone de livraison',
            'opening_hours' => 'horaires',
            'order_conditions' => 'conditions de commande',
            'facebook_url' => 'lien Facebook',
            'instagram_url' => 'lien Instagram',
            'tiktok_url' => 'lien TikTok',
        ];
    }
}
