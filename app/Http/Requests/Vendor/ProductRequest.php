<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product ? $this->user()->can('update', $product) : $this->user()->can('manageCatalog', $this->route('store'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'price' => $this->cleanAmount($this->input('price')),
            'sale_price' => $this->cleanAmount($this->input('sale_price')),
        ]);
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $max = config('gkas.uploads.max_product_images');

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            // La catégorie doit appartenir à la même boutique.
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('store_id', $store->id)],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:'.$max],
            'images.*' => [
                'image',
                'mimes:'.implode(',', config('gkas.uploads.mimes')),
                'max:'.config('gkas.uploads.max_kb'),
                'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom du produit',
            'price' => 'prix',
            'sale_price' => 'prix promotionnel',
            'category_id' => 'catégorie',
            'stock_quantity' => 'quantité disponible',
            'images.*' => 'image',
        ];
    }

    public function messages(): array
    {
        return ['sale_price.lt' => 'Le prix promotionnel doit être inférieur au prix normal.'];
    }

    /** « 8 000 » → « 8000 » (les espaces de milliers sont tolérés). */
    private function cleanAmount(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? preg_replace('/[\s\x{00A0}\x{202F}]+/u', '', $value) : $value;
    }
}
