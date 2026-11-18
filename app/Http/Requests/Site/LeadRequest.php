<?php

namespace App\Http\Requests\Site;

use App\Models\DemoRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{8,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'activity' => ['nullable', 'string', 'max:150'],
            'social_network' => ['nullable', Rule::in(array_keys(DemoRequest::socialNetworks()))],
            'social_handle' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:255'], // champ piège anti-robot
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'business_name' => 'nom de la boutique',
            'phone' => 'téléphone',
            'activity' => 'activité',
            'social_network' => 'réseau social',
            'social_handle' => 'compte réseau social',
            'city' => 'ville',
        ];
    }
}
