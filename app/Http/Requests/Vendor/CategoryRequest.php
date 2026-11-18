<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category ? $this->user()->can('update', $category) : $this->user()->can('manageCatalog', $this->route('store'));
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'name')->where('store_id', $store->id)->ignore($category?->id),
            ],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom de la catégorie'];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            $data['position'] ??= 0;
        }

        return $data;
    }
}
