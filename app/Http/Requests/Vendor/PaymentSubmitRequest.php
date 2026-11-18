<?php

namespace App\Http\Requests\Vendor;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        return $store && ($this->user()->can('manageBilling', $store) || $this->user()->can('recordPayment', $store));
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'transaction_reference' => [
                'required', 'string', 'min:4', 'max:100',
                Rule::unique('payments')->where('method', $this->input('method')),
            ],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'payer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'plan_id' => 'formule',
            'method' => 'moyen de paiement',
            'transaction_reference' => 'référence de transaction',
            'proof' => 'justificatif',
        ];
    }

    public function messages(): array
    {
        return ['transaction_reference.unique' => 'Cette référence de transaction a déjà été utilisée.'];
    }
}
