<?php

namespace App\Http\Requests\Vendor;

use App\Enums\StoreTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSettings', $this->route('store'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function rules(): array
    {
        return self::brandingRules() + [
            'theme' => ['required', Rule::enum(StoreTheme::class)],
            'is_published' => ['boolean'],
        ];
    }

    public static function brandingRules(): array
    {
        $image = ['nullable', 'image', 'mimes:'.implode(',', config('gkas.uploads.mimes')), 'max:'.config('gkas.uploads.max_kb')];

        return [
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => [...$image, 'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000'],
            'banner' => [...$image, 'dimensions:min_width=600,min_height=150,max_width=6000,max_height=4000'],
        ];
    }

    public function attributes(): array
    {
        return ['primary_color' => 'couleur principale', 'logo' => 'logo', 'banner' => 'bannière', 'theme' => 'thème'];
    }
}
