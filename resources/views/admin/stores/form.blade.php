@extends('admin._layout', ['title' => $store->exists ? 'Modifier la boutique' : 'Nouvelle boutique'])

@section('content')
    <x-page-header :title="$store->exists ? 'Modifier « '.$store->name.' »' : 'Créer une boutique'"
        :subtitle="$store->exists ? null : 'L\'essai gratuit de '.\App\Models\Setting::trialDays().' jours démarre à la création.'"
        :back="$store->exists ? route('admin.stores.show', $store) : route('admin.stores.index')" />

    @if ($prospect)
        <p class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">Création à partir du prospect <strong>{{ $prospect->name }}</strong> ({{ $prospect->phone }}).</p>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ $store->exists ? route('admin.stores.update', $store) : route('admin.stores.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($store->exists) @method('PUT') @endif
        @if ($prospect)<input type="hidden" name="prospect_id" value="{{ $prospect->id }}">@endif

        <div class="space-y-6 lg:col-span-2">
            @unless ($store->exists)
                <div class="card card-body space-y-4">
                    <h2 class="font-semibold text-slate-900">Propriétaire de la boutique</h2>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="radio" name="owner_mode" value="new" @checked(old('owner_mode', 'new') === 'new')> Nouveau vendeur</label>
                        <label class="flex items-center gap-2"><input type="radio" name="owner_mode" value="existing" @checked(old('owner_mode') === 'existing') @disabled($vendors->isEmpty())> Vendeur existant</label>
                    </div>
                    <div data-show-when="owner_mode=new" class="grid gap-4 sm:grid-cols-3">
                        <x-form.input name="owner_name" label="Nom du vendeur" :value="$prospect?->name" />
                        <x-form.input name="owner_email" type="email" label="E-mail (identifiant)" :value="$prospect?->email" />
                        <x-form.input name="owner_phone" label="Téléphone" :value="$prospect?->phone" />
                        <p class="text-xs text-slate-500 sm:col-span-3">Un mot de passe temporaire sera généré et affiché une seule fois après la création.</p>
                    </div>
                    <div data-show-when="owner_mode=existing" hidden>
                        <x-form.select name="owner_id" label="Vendeur" :options="$vendors->mapWithKeys(fn ($v) => [$v->id => $v->name.' — '.$v->email])" placeholder="— Choisir —" />
                    </div>
                </div>
            @endunless

            <div class="card card-body space-y-4">
                <h2 class="font-semibold text-slate-900">Adresse publique</h2>
                <div>
                    <label for="f-slug" class="form-label">Identifiant public</label>
                    <div class="flex rounded-lg shadow-sm">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ str_replace(['http://', 'https://'], '', url('/')) }}/</span>
                        <input id="f-slug" name="slug" value="{{ old('slug', $store->slug) }}" @required($store->exists) pattern="[a-z0-9]+(-[a-z0-9]+)*" minlength="3" maxlength="60" class="form-input rounded-l-none font-mono {{ $errors->has('slug') ? 'is-invalid' : '' }}" placeholder="{{ $store->exists ? '' : 'généré à partir du nom' }}">
                    </div>
                    <p class="form-help">Lettres minuscules, chiffres et tirets. Modifier l'identifiant change le lien partagé par le vendeur.</p>
                    @error('slug')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @include('seller._store-fields')
        </div>

        <div class="space-y-6">
            <div class="card card-body space-y-4">
                <h2 class="font-semibold text-slate-900">Identité visuelle</h2>
                <x-form.select name="theme" label="Thème" :options="collect($themes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="$store->theme" required />
                <div>
                    <label class="form-label" for="primary_color">Couleur principale</label>
                    <div class="flex gap-2">
                        <input type="color" value="{{ old('primary_color', $store->primary_color) }}" data-color-sync="primary_color" class="h-11 w-14 rounded-lg border border-slate-300 p-1" aria-label="Couleur">
                        <input id="primary_color" name="primary_color" value="{{ old('primary_color', $store->primary_color) }}" required pattern="#[0-9a-fA-F]{6}" class="form-input font-mono">
                    </div>
                    @error('primary_color')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                @foreach (['logo' => ['Logo', $store->logoUrl()], 'banner' => ['Bannière', $store->bannerUrl()]] as $field => [$label, $url])
                    <div>
                        <label class="form-label">{{ $label }}</label>
                        @if ($url)<img src="{{ $url }}" alt="" class="mb-2 h-16 rounded object-cover">@endif
                        <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600">
                        @error($field)<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <div class="card card-body space-y-4">
                <x-form.checkbox name="is_published" label="Boutique publiée" :checked="$store->is_published" />
                <x-form.checkbox name="is_demo" label="Boutique de démonstration" :checked="$store->is_demo" help="Affichée sur la page « Démonstration » du site." />
            </div>
            <button class="btn btn-primary w-full">{{ $store->exists ? 'Enregistrer' : 'Créer la boutique et démarrer l\'essai' }}</button>
        </div>
    </form>
@endsection
