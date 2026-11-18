@extends('seller._layout', ['title' => 'Livraison'])

@section('content')
    <x-page-header title="Livraison" subtitle="Vos zones de livraison et leurs frais. Le client choisit sa zone en commandant ; les frais sont ajoutés automatiquement au total." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <form method="POST" action="{{ route('vendor.delivery.store', $store) }}" class="card card-body space-y-4">
                @csrf
                <h2 class="font-semibold text-slate-900">Nouvelle zone</h2>
                <x-form.input name="name" label="Nom de la zone" required maxlength="100" placeholder="ex. Lomé centre, Agoè, Retrait en boutique" />
                <x-form.input name="fee" type="number" label="Frais (FCFA)" required min="0" step="1" inputmode="numeric" help="0 pour une livraison gratuite ou un retrait sur place." />
                <x-form.input name="description" label="Précision (facultatif)" maxlength="255" placeholder="ex. Livré sous 24 h" />
                <input type="hidden" name="is_active" value="1">
                <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> Ajouter la zone</button>
            </form>

            <form method="POST" action="{{ route('vendor.delivery.options', $store) }}" class="card card-body space-y-4">
                @csrf @method('PUT')
                <h2 class="font-semibold text-slate-900">Livraison offerte</h2>
                <x-form.input name="free_delivery_threshold" type="number" label="À partir de (FCFA)" :value="$store->free_delivery_threshold !== null ? (int) $store->free_delivery_threshold : null" min="0" step="1"
                    help="Si le montant des articles atteint ce seuil, la livraison est gratuite. Laissez vide pour désactiver." />
                <button class="btn btn-secondary w-full">Enregistrer</button>
            </form>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            @forelse ($zones as $zone)
                <form method="POST" action="{{ route('vendor.delivery.update', [$store, $zone]) }}" class="grid gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:grid-cols-12 sm:items-end">
                    @csrf @method('PUT')
                    <div class="sm:col-span-4">
                        <label class="form-label text-xs">Zone</label>
                        <input name="name" value="{{ $zone->name }}" required maxlength="100" class="form-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label text-xs">Frais</label>
                        <input name="fee" type="number" min="0" step="1" value="{{ (int) $zone->fee }}" required class="form-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="form-label text-xs">Précision</label>
                        <input name="description" value="{{ $zone->description }}" maxlength="255" class="form-input">
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-3 sm:justify-end">
                        <label class="flex items-center gap-1.5 text-xs text-slate-600" title="Proposée aux clients">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($zone->is_active) class="rounded text-brand-600"> Active
                        </label>
                        <button class="btn btn-secondary btn-sm">OK</button>
                        <button type="submit" form="delete-zone-{{ $zone->id }}" class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50" title="Supprimer"><x-icon name="trash" class="size-4" /></button>
                    </div>
                </form>
                <form id="delete-zone-{{ $zone->id }}" method="POST" action="{{ route('vendor.delivery.destroy', [$store, $zone]) }}" data-confirm="Supprimer la zone « {{ $zone->name }} » ? Les commandes passées gardent leurs frais." class="hidden">
                    @csrf @method('DELETE')
                </form>
            @empty
                <x-empty icon="truck" title="Aucune zone de livraison" text="Sans zone, les clients commandent sans frais de livraison et vous convenez des frais directement avec eux." />
            @endforelse
        </div>
    </div>
@endsection
