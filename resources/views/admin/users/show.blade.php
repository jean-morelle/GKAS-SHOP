@extends('admin._layout', ['title' => $user->name])

@section('content')
    <x-page-header :title="$user->name" :subtitle="$user->role->label()" :back="route('admin.users.index', ['role' => $user->role->value])">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Modifier</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">E-mail</dt><dd class="font-medium">{{ $user->email }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Compte</dt><dd><x-badge :color="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</x-badge> @if ($user->must_change_password)<x-badge color="amber">Mot de passe temporaire</x-badge>@endif</dd></div>
                    <div><dt class="text-slate-500">Dernière connexion</dt><dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                    <div><dt class="text-slate-500">Créé le</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </div>

            @if ($user->isVendor())
                <div class="card overflow-hidden">
                    <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Boutiques</h2>
                    @forelse ($user->stores as $s)
                        <a href="{{ route('admin.stores.show', $s) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0 hover:bg-slate-50">
                            <span><span class="font-medium">{{ $s->name }}</span> <span class="text-xs text-slate-500">· {{ $s->pivot->role === 'owner' ? 'Propriétaire' : 'Membre' }}</span></span>
                            <x-badge :color="$s->accessState()->color()">{{ $s->accessState()->label() }}</x-badge>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500">Aucune boutique. <a href="{{ route('admin.stores.create') }}" class="text-brand-700 underline">Créer une boutique</a></p>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.users.reset-access', $user) }}" class="card card-body space-y-3" data-confirm="Générer un nouveau mot de passe temporaire pour {{ $user->name }} ? L'ancien ne fonctionnera plus.">
                @csrf
                <h2 class="font-semibold text-slate-900">Réinitialiser l'accès</h2>
                <p class="text-sm text-slate-600">Génère un mot de passe temporaire à communiquer au titulaire ; il devra le changer à la connexion.</p>
                <button class="btn btn-secondary w-full"><x-icon name="lock" class="size-4" /> Générer un mot de passe temporaire</button>
            </form>
            @unless ($user->is(auth()->user()))
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="card card-body space-y-3" data-confirm="{{ $user->is_active ? 'Désactiver ce compte ? L\'utilisateur ne pourra plus se connecter. Ses boutiques ne sont pas supprimées.' : 'Réactiver ce compte ?' }}">
                    @csrf
                    <h2 class="font-semibold text-slate-900">{{ $user->is_active ? 'Désactiver le compte' : 'Réactiver le compte' }}</h2>
                    <button class="btn {{ $user->is_active ? 'btn-danger' : 'btn-primary' }} w-full">{{ $user->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                </form>
            @endunless
        </div>
    </div>
@endsection
