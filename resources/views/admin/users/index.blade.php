@extends('admin._layout', ['title' => 'Utilisateurs'])

@section('content')
    <x-page-header title="Utilisateurs" subtitle="Comptes vendeurs et équipe GKAS-GROUP.">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Créer un compte</a>
    </x-page-header>

    <div class="mb-4 flex gap-2">
        @foreach (\App\Enums\UserRole::cases() as $r)
            <a href="{{ route('admin.users.index', ['role' => $r->value]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => $role === $r, 'bg-white text-slate-600 ring-1 ring-slate-200' => $role !== $r])>{{ $r === \App\Enums\UserRole::Vendor ? 'Vendeurs' : $r->label().'s' }}</a>
        @endforeach
    </div>

    <form class="card mb-4 flex gap-2 p-4" role="search">
        <input type="hidden" name="role" value="{{ $role?->value }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="Nom, e-mail ou téléphone…" class="form-input" aria-label="Rechercher">
        <button class="btn btn-secondary">Rechercher</button>
    </form>

    <div class="card overflow-hidden">
        @if ($users->isEmpty())
            <x-empty icon="users" title="Aucun compte" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Nom</th><th>Contact</th><th>Boutiques</th><th>Dernière connexion</th><th>Compte</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $u)
                            <tr>
                                <td><a href="{{ route('admin.users.show', $u) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $u->name }}</a></td>
                                <td>{{ $u->email }}<span class="block text-xs text-slate-500">{{ $u->phone }}</span></td>
                                <td>{{ $u->stores_count }}</td>
                                <td class="whitespace-nowrap">{{ $u->last_login_at?->diffForHumans() ?? 'Jamais' }}</td>
                                <td><x-badge :color="$u->is_active ? 'green' : 'red'">{{ $u->is_active ? 'Actif' : 'Désactivé' }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        @endif
    </div>
@endsection
