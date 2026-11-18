@extends('seller._layout', ['title' => 'Ma boutique'])

@section('content')
    <x-page-header title="Ma boutique" subtitle="Les informations affichées à vos clients.">
        <a href="{{ $store->publicUrl() }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" class="size-4" /> Voir la boutique</a>
    </x-page-header>

    <div class="card card-body mb-6 grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <p class="text-slate-500">Adresse sur GKAS Shop</p>
            <p class="font-mono font-medium text-slate-900">{{ $store->platformUrl() }}</p>
        </div>
        <div>
            <p class="text-slate-500">Nom de domaine personnalisé</p>
            @if ($store->custom_domain)
                <p class="font-mono font-medium text-slate-900">{{ $store->customDomainUrl() }}</p>
            @else
                <p class="text-slate-600">Aucun. Vous souhaitez <span class="font-mono">votreboutique.com</span> ? Contactez GKAS-GROUP{{ $platform['contact_phone'] ? ' au '.$platform['contact_phone'] : '' }} : nous configurons le domaine pour vous.</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('vendor.settings.update', $store) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('seller._store-fields')
        <div class="flex justify-end"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
@endsection
