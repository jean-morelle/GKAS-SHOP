@extends('seller._layout', ['title' => 'Équipe'])

@section('content')
    <x-page-header title="Équipe" subtitle="Donnez accès à votre boutique à vos collaborateurs, avec des droits limités." />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('vendor.team.store', $store) }}" class="card card-body h-fit space-y-4">
            @csrf
            <h2 class="font-semibold text-slate-900">Ajouter un membre</h2>
            <x-form.input name="name" label="Nom" required maxlength="120" />
            <x-form.input name="email" type="email" label="E-mail (identifiant de connexion)" required />
            <x-form.input name="phone" label="Téléphone (facultatif)" />
            <fieldset class="space-y-2">
                <legend class="form-label">Rôle</legend>
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 has-checked:border-brand-500 has-checked:bg-brand-50">
                        <input type="radio" name="role" value="{{ $role->value }}" required @checked(old('role', 'staff') === $role->value) class="mt-1 text-brand-600">
                        <span><span class="block text-sm font-medium text-slate-900">{{ $role->label() }}</span><span class="text-xs text-slate-500">{{ $role->description() }}</span></span>
                    </label>
                @endforeach
                @error('role')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>
            <p class="text-xs text-slate-500">Si la personne n'a pas encore de compte, un mot de passe temporaire est créé et affiché une seule fois.</p>
            <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> Ajouter</button>
        </form>

        <div class="card overflow-hidden lg:col-span-2">
            @foreach ($members as $member)
                @php
                    $role = \App\Enums\StoreRole::tryFrom($member->pivot->role);
                    $editable = $role !== \App\Enums\StoreRole::Owner && ! $member->is(auth()->user());
                @endphp
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $member->name }} @if ($member->is(auth()->user()))<span class="text-xs text-slate-400">(vous)</span>@endif</p>
                        <p class="truncate text-xs text-slate-500">{{ $member->email }} · dernière connexion : {{ $member->last_login_at?->diffForHumans() ?? 'jamais' }}</p>
                    </div>
                    @if ($editable)
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('vendor.team.update', [$store, $member]) }}" class="flex items-center gap-2">
                                @csrf @method('PUT')
                                <select name="role" class="form-input py-1.5 text-sm" aria-label="Rôle de {{ $member->name }}" onchange="this.form.submit()">
                                    @foreach ($roles as $r)<option value="{{ $r->value }}" @selected($role === $r)>{{ $r->label() }}</option>@endforeach
                                </select>
                                <noscript><button class="btn btn-secondary btn-sm">OK</button></noscript>
                            </form>
                            <form method="POST" action="{{ route('vendor.team.destroy', [$store, $member]) }}" data-confirm="Retirer l'accès de {{ $member->name }} à la boutique ?">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50" title="Retirer"><x-icon name="trash" class="size-4" /></button>
                            </form>
                        </div>
                    @else
                        <x-badge :color="$role === \App\Enums\StoreRole::Owner ? 'indigo' : 'gray'">{{ $role?->label() }}</x-badge>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
