@extends('admin._layout', ['title' => 'Prospects'])

@section('content')
    <x-page-header title="Prospects et demandes" subtitle="Demandes de démonstration, messages de contact et prospects saisis par l'équipe.">
        <a href="{{ route('admin.prospects.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Ajouter un prospect</a>
    </x-page-header>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('admin.prospects.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => ! $status, 'bg-white text-slate-600 ring-1 ring-slate-200' => $status])>Tous</a>
        @foreach (\App\Enums\ProspectStatus::cases() as $s)
            <a href="{{ route('admin.prospects.index', ['statut' => $s->value]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $status === $s, 'bg-white text-slate-600 ring-1 ring-slate-200' => $status !== $s])>
                {{ $s->label() }} <span class="opacity-75">({{ $counts[$s->value] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <form class="card mb-4 flex flex-col gap-2 p-4 sm:flex-row sm:items-center" role="search">
        @if ($status)<input type="hidden" name="statut" value="{{ $status->value }}">@endif
        <input type="search" name="q" value="{{ $search }}" placeholder="Nom, boutique ou téléphone…" class="form-input" aria-label="Rechercher">
        <label class="flex items-center gap-2 text-sm whitespace-nowrap"><input type="checkbox" name="mes-prospects" value="1" @checked($mine)> Mes prospects</label>
        <button class="btn btn-secondary">Filtrer</button>
    </form>

    <div class="card overflow-hidden">
        @if ($prospects->isEmpty())
            <x-empty icon="megaphone" title="Aucun prospect" text="Les demandes envoyées depuis le site public apparaîtront ici." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Prospect</th><th class="max-md:hidden">Activité / réseau</th><th>Étape</th><th class="max-lg:hidden">Commercial</th><th>Relance</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($prospects as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.prospects.show', $p) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $p->business_name ?: $p->name }}</a>
                                    <span class="block text-xs text-slate-500">{{ $p->name }} · {{ $p->phone }} · {{ $p->typeLabel() }}</span>
                                </td>
                                <td class="max-md:hidden">{{ $p->activity ?: '—' }}<span class="block text-xs text-slate-500">{{ \App\Models\DemoRequest::socialNetworks()[$p->social_network] ?? '' }} {{ $p->social_handle }}</span></td>
                                <td><x-badge :color="$p->status->color()">{{ $p->status->label() }}</x-badge></td>
                                <td class="max-lg:hidden">{{ $p->assignee?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($p->next_follow_up_at)
                                        <x-badge :color="$p->next_follow_up_at->isPast() && ! $p->next_follow_up_at->isToday() ? 'red' : 'blue'">{{ $p->next_follow_up_at->format('d/m/Y') }}</x-badge>
                                    @else — @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $prospects->links() }}
        @endif
    </div>
@endsection
