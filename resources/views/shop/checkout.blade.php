@extends('layouts.shop', ['title' => 'Commander'])

@section('content')
    @include('shop._steps', ['step' => 1])

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ shop_route('checkout.review', $store) }}" class="rounded-shop space-y-4 border border-black/5 bg-white p-5 text-slate-900 lg:col-span-2">
            @csrf
            <h1 class="text-xl font-extrabold">Vos coordonnées</h1>
            <p class="text-sm text-slate-500">Pas besoin de créer un compte. Ces informations servent uniquement à la livraison de votre commande.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Nom complet" :value="$customer['name'] ?? null" required autocomplete="name" />
                <x-form.input name="phone" type="tel" label="Téléphone (WhatsApp de préférence)" :value="$customer['phone'] ?? null" required autocomplete="tel" />
            </div>
            @if ($zones->isNotEmpty())
                <fieldset>
                    <legend class="form-label">Zone de livraison <span class="text-red-500" aria-hidden="true">*</span></legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($zones as $zone)
                            <label class="rounded-shop flex cursor-pointer items-start justify-between gap-3 border border-slate-200 p-3 hover:bg-slate-50 has-checked:border-shop-primary has-checked:bg-slate-50">
                                <span class="flex items-start gap-2">
                                    <input type="radio" name="delivery_zone_id" value="{{ $zone->id }}" required @checked((int) old('delivery_zone_id', $customer['delivery_zone_id'] ?? 0) === $zone->id) class="mt-1">
                                    <span><span class="block text-sm font-medium">{{ $zone->name }}</span>@if ($zone->description)<span class="block text-xs text-slate-500">{{ $zone->description }}</span>@endif</span>
                                </span>
                                <span class="text-sm font-semibold whitespace-nowrap">{{ bccomp((string) $zone->fee, '0', 2) > 0 ? fcfa($zone->fee) : 'Gratuit' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($store->free_delivery_threshold !== null)
                        <p class="form-help">Livraison offerte dès {{ fcfa($store->free_delivery_threshold) }} d'achat.</p>
                    @endif
                    @error('delivery_zone_id')<p class="form-error">{{ $message }}</p>@enderror
                </fieldset>
            @endif
            <x-form.input name="area" label="Commune ou quartier" :value="$customer['area'] ?? null" required placeholder="ex. Bè, Tokoin, Agoè…" />
            <x-form.textarea name="address" label="Adresse ou instructions de livraison" :value="$customer['address'] ?? null" rows="3" required placeholder="Repère, rue, couleur du portail…" />
            <x-form.textarea name="notes" label="Notes pour le vendeur (facultatif)" :value="$customer['notes'] ?? null" rows="2" placeholder="Taille, couleur, horaire préféré…" />
            <button class="btn-shop w-full sm:w-auto">Vérifier ma commande <x-icon name="arrow-right" class="size-4" /></button>
        </form>

        @include('shop._order-aside')
    </div>
@endsection
