@extends('admin._layout', ['title' => 'Boutiques'])

@section('content')
    <x-page-header title="Boutiques" subtitle="Toutes les boutiques hébergées sur la plateforme.">
        @can('create', \App\Models\Store::class)
            <a href="{{ route('admin.stores.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Créer une boutique</a>
        @endcan
    </x-page-header>

    <form class="card mb-4 flex flex-col gap-2 p-4 sm:flex-row" role="search">
        <input type="search" name="q" value="{{ $search }}" placeholder="Nom, identifiant ou e-mail du vendeur…" class="form-input" aria-label="Rechercher">
        <select name="acces" class="form-input sm:max-w-56" aria-label="État">
            <option value="">Tous les états</option>
            @foreach (\App\Enums\AccessState::cases() as $a)<option value="{{ $a->value }}" @selected($access === $a)>{{ $a->label() }}</option>@endforeach
        </select>
        <button class="btn btn-secondary">Filtrer</button>
    </form>

    <div class="card overflow-hidden">
        @if ($stores->isEmpty())
            <x-empty icon="store" title="Aucune boutique" text="Créez la première boutique d'un vendeur pour démarrer son essai gratuit." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Boutique</th><th class="max-md:hidden">Vendeur</th><th>État</th><th>Échéance</th><th class="max-lg:hidden">Produits / cmd</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stores as $store)
                            @php $state = $store->accessState(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.stores.show', $store) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $store->name }}</a>
                                    @if ($store->is_demo)<x-badge color="purple">Démo</x-badge>@endif
                                    <span class="block font-mono text-xs text-slate-500">/{{ $store->slug }}</span>
                                </td>
                                <td class="max-md:hidden">{{ $store->owner?->name ?? '—' }}<span class="block text-xs text-slate-500">{{ $store->owner?->email }}</span></td>
                                <td><x-badge :color="$state->color()">{{ $state->label() }}</x-badge></td>
                                <td class="text-sm whitespace-nowrap">
                                    @if ($store->accessEndsAt())
                                        {{ $store->accessEndsAt()->format('d/m/Y') }}
                                        @if ($state->isOperational())<span class="block text-xs text-slate-500">{{ $store->daysRemaining() }} j restants</span>@endif
                                    @else — @endif
                                </td>
                                <td class="max-lg:hidden">{{ $store->products_count }} / {{ $store->orders_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $stores->links() }}
        @endif
    </div>
@endsection
