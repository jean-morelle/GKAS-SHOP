@extends('seller._layout', ['title' => 'Apparence'])

@section('content')
    <x-page-header title="Apparence" subtitle="Logo, couleurs et thème de votre boutique.">
        <a href="{{ $store->publicUrl() }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" class="size-4" /> Prévisualiser</a>
    </x-page-header>

    <form method="POST" action="{{ route('vendor.appearance.update', $store) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf @method('PUT')
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Thème</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($themes as $theme)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50 has-checked:border-brand-500 has-checked:ring-2 has-checked:ring-brand-500/20">
                            <input type="radio" name="theme" value="{{ $theme->value }}" @checked(old('theme', $store->theme?->value) === $theme->value) class="mt-1 text-brand-600">
                            <span><span class="block font-medium text-slate-900">{{ $theme->label() }}</span><span class="text-sm text-slate-500">{{ $theme->description() }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Couleur principale</h2>
                <div class="mt-4 flex items-center gap-3">
                    <input type="color" id="color-picker" value="{{ old('primary_color', $store->primary_color) }}" data-color-sync="primary_color" class="h-11 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white p-1" aria-label="Choisir une couleur">
                    <input type="text" id="primary_color" name="primary_color" value="{{ old('primary_color', $store->primary_color) }}" pattern="#[0-9a-fA-F]{6}" class="form-input w-32 font-mono" aria-label="Code couleur">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['#4f46e5', '#db2777', '#059669', '#d97706', '#dc2626', '#0f172a', '#7c3aed', '#0891b2'] as $c)
                            <button type="button" class="size-8 rounded-full ring-2 ring-white shadow" style="background: {{ $c }}" onclick="document.getElementById('primary_color').value='{{ $c }}';document.getElementById('color-picker').value='{{ $c }}';document.getElementById('color-picker').dispatchEvent(new Event('input',{bubbles:true}))" aria-label="Couleur {{ $c }}"></button>
                        @endforeach
                    </div>
                </div>
                @error('primary_color')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="card card-body grid gap-6 sm:grid-cols-2">
                @foreach (['logo' => ['Logo', $store->logoUrl(), 'Carré de préférence, 64 px minimum.'], 'banner' => ['Bannière', $store->bannerUrl(), 'Format large (ex. 1600 × 500 px).']] as $field => [$label, $url, $help])
                    <div>
                        <p class="form-label">{{ $label }}</p>
                        @if ($url)
                            <img src="{{ $url }}" alt="" class="mb-2 {{ $field === 'logo' ? 'size-20 rounded-full' : 'h-24 w-full rounded-lg' }} object-cover">
                            <label class="mb-2 flex items-center gap-2 text-sm text-red-600"><input type="checkbox" name="remove_{{ $field }}" value="1"> Supprimer</label>
                        @endif
                        <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-medium file:text-brand-700">
                        <p class="form-help">{{ $help }} JPG, PNG ou WEBP, 2 Mo max.</p>
                        @error($field)<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            {{-- Aperçu en direct de la couleur --}}
            <div class="card overflow-hidden shop" data-color-preview style="--shop-primary: {{ $store->primary_color }}">
                <div class="bg-shop-primary px-4 py-5 text-white">
                    <p class="text-lg font-bold">{{ $store->name }}</p>
                    <p class="text-sm opacity-80">Aperçu des couleurs</p>
                </div>
                <div class="p-4">
                    <p class="text-shop-primary text-xl font-extrabold">12 500 FCFA</p>
                    <span class="btn-shop mt-3 w-full text-sm">Ajouter au panier</span>
                </div>
            </div>

            <div class="card card-body">
                <x-form.checkbox name="is_published" label="Boutique publiée" :checked="$store->is_published" help="Décochée, seule vous pouvez voir la boutique (mode prévisualisation)." />
            </div>
            <button class="btn btn-primary w-full">Enregistrer l'apparence</button>
        </div>
    </form>
@endsection
