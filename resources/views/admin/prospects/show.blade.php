@extends('admin._layout', ['title' => $prospect->name])

@section('content')
    <x-page-header :title="$prospect->business_name ?: $prospect->name" :subtitle="$prospect->typeLabel().' reçue le '.$prospect->created_at->format('d/m/Y à H:i')" :back="route('admin.prospects.index')">
        @if ($prospect->store)
            <a href="{{ route('admin.stores.show', $prospect->store) }}" class="btn btn-secondary"><x-icon name="store" class="size-4" /> Voir sa boutique</a>
        @else
            @can('create', \App\Models\Store::class)
                <a href="{{ route('admin.stores.create', ['prospect' => $prospect->id]) }}" class="btn btn-primary"><x-icon name="store" class="size-4" /> Créer sa boutique d'essai</a>
            @endcan
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Nom</dt><dd class="font-medium">{{ $prospect->name }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd class="flex items-center gap-2">
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $prospect->phone) }}" class="font-medium text-brand-700">{{ $prospect->phone }}</a>
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $prospect->phone) }}" target="_blank" rel="noopener" class="text-emerald-600" title="WhatsApp"><x-icon name="whatsapp" class="size-5" /></a>
                    </dd></div>
                    <div><dt class="text-slate-500">Activité</dt><dd>{{ $prospect->activity ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Réseau social</dt><dd>{{ \App\Models\DemoRequest::socialNetworks()[$prospect->social_network] ?? '—' }} {{ $prospect->social_handle }}</dd></div>
                    <div><dt class="text-slate-500">Ville</dt><dd>{{ $prospect->city ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">E-mail</dt><dd>{{ $prospect->email ?: '—' }}</dd></div>
                    @if ($prospect->message)<div class="sm:col-span-2"><dt class="text-slate-500">Message</dt><dd class="rounded bg-slate-50 p-3 whitespace-pre-line">{{ $prospect->message }}</dd></div>@endif
                </dl>
            </div>

            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Notes commerciales</h2>
                <form method="POST" action="{{ route('admin.prospects.notes.store', $prospect) }}" class="mt-4 space-y-2">
                    @csrf
                    <x-form.textarea name="body" rows="2" placeholder="Compte rendu d'appel, besoins, objections…" required />
                    <button class="btn btn-secondary btn-sm">Ajouter la note</button>
                </form>
                <ul class="mt-6 space-y-4">
                    @forelse ($prospect->notes as $note)
                        <li class="border-l-2 border-brand-200 pl-4 text-sm">
                            <p class="whitespace-pre-line text-slate-800">{{ $note->body }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $note->user?->name ?? '—' }} · {{ $note->created_at->format('d/m/Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Aucune note.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.prospects.update', $prospect) }}" class="card card-body h-fit space-y-4">
            @csrf @method('PUT')
            <h2 class="font-semibold text-slate-900">Suivi</h2>
            <x-form.select name="status" label="Étape" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$prospect->status" required />
            <x-form.select name="assigned_to" label="Commercial affecté" :options="$staff->pluck('name', 'id')" :value="$prospect->assigned_to" placeholder="— Non affecté —" />
            <x-form.input name="next_follow_up_at" type="date" label="Date de relance" :value="$prospect->next_follow_up_at?->format('Y-m-d')" />
            <button class="btn btn-primary w-full">Enregistrer le suivi</button>
        </form>
    </div>
@endsection
