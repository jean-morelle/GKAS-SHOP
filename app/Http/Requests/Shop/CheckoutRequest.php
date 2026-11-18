<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{8,30}$/'],
            'area' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Zone obligatoire dès que la boutique a défini des zones de livraison.
            'delivery_zone_id' => [
                $this->storeHasZones() ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_zones', 'id')->where('store_id', $this->route('store')->id)->where('is_active', true),
            ],
        ];
    }

    private function storeHasZones(): bool
    {
        return $this->route('store')->deliveryZones()->where('is_active', true)->exists();
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'phone' => 'numéro de téléphone',
            'area' => 'commune ou quartier',
            'address' => 'adresse ou instructions de livraison',
            'notes' => 'notes',
            'delivery_zone_id' => 'zone de livraison',
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Le numéro de téléphone n\'est pas valide (8 chiffres minimum).'];
    }
}
