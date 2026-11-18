@extends('admin._layout', ['title' => $user->exists ? 'Modifier le compte' : 'Nouveau compte'])

@section('content')
    <x-page-header :title="$user->exists ? 'Modifier '.$user->name : 'Créer un compte'" :back="$user->exists ? route('admin.users.show', $user) : route('admin.users.index')" />

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card max-w-2xl">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="card-body space-y-4">
            <x-form.input name="name" label="Nom complet" :value="$user->name" required />
            <x-form.input name="email" type="email" label="E-mail (identifiant de connexion)" :value="$user->email" required />
            <x-form.input name="phone" label="Téléphone" :value="$user->phone" />
            <x-form.select name="role" label="Rôle" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :value="$user->role ?? \App\Enums\UserRole::Vendor" required
                help="Administrateur : tous les droits. Commercial : prospects et création de boutiques. Vendeur : ses boutiques uniquement." />
            @unless ($user->exists)
                <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Un mot de passe temporaire sera généré et affiché une seule fois. L'utilisateur devra le changer à sa première connexion.</p>
            @endunless
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4"><button class="btn btn-primary">{{ $user->exists ? 'Enregistrer' : 'Créer le compte' }}</button></div>
    </form>
@endsection
