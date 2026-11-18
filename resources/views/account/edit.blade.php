@extends($layout, ['title' => 'Mon profil'])

@section('content')
    <x-page-header title="Mon profil" subtitle="Vos informations personnelles et votre sécurité." />

    @if ($user->must_change_password)
        <div class="mb-6 flex items-start gap-3 rounded-xl border-2 border-amber-400 bg-amber-50 p-4 text-sm text-amber-950" role="alert">
            <x-icon name="lock" class="mt-0.5 size-6 shrink-0" />
            <div>
                <p class="text-base font-semibold">Étape obligatoire : choisissez votre mot de passe personnel</p>
                <p class="mt-1">Votre compte utilise un mot de passe temporaire. Tant qu'il n'est pas changé, les autres pages
                    {{ $user->isStaff() ? 'de l\'administration' : 'de votre espace' }} restent bloquées et vous ramènent ici.</p>
                <p class="mt-1">Dans le formulaire « Changer mon mot de passe » : saisissez le mot de passe temporaire dans <strong>Mot de passe actuel</strong>, puis votre nouveau mot de passe deux fois.</p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('account.update') }}" class="card">
            @csrf @method('PUT')
            <div class="card-body space-y-4">
                <h2 class="font-semibold text-slate-900">Informations</h2>
                <x-form.input name="name" label="Nom complet" :value="$user->name" required />
                <x-form.input name="email" type="email" label="Adresse e-mail" :value="$user->email" required />
                <x-form.input name="phone" label="Téléphone" :value="$user->phone" />
                <p class="text-xs text-slate-500">Rôle : {{ $user->role->label() }}</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button class="btn btn-primary">Enregistrer</button>
            </div>
        </form>

        <form method="POST" action="{{ route('account.password') }}" class="card">
            @csrf @method('PUT')
            <div class="card-body space-y-4">
                <h2 class="font-semibold text-slate-900">Changer mon mot de passe</h2>
                <x-form.input name="current_password" type="password" label="Mot de passe actuel" required autocomplete="current-password" />
                <x-form.input name="password" type="password" label="Nouveau mot de passe" required autocomplete="new-password" help="8 caractères minimum, avec des lettres et des chiffres." />
                <x-form.input name="password_confirmation" type="password" label="Confirmation" required autocomplete="new-password" />
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button class="btn btn-primary">Modifier le mot de passe</button>
            </div>
        </form>
    </div>

    @if ($user->isVendor() && $stores->isNotEmpty())
        <div class="card mt-6">
            <div class="card-body">
                <h2 class="font-semibold text-slate-900">Mes boutiques et abonnements</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($stores as $s)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $s->name }}</p>
                                <p class="text-xs text-slate-500">{{ $s->publicUrl() }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-badge :color="$s->accessState()->color()">{{ $s->accessState()->label() }}</x-badge>
                                @if ($s->accessEndsAt())<span class="text-xs text-slate-500">jusqu'au {{ $s->accessEndsAt()->format('d/m/Y') }}</span>@endif
                                <a href="{{ route('vendor.subscription.show', $s) }}" class="btn btn-secondary btn-sm">Abonnement</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
