@extends('admin._layout', ['title' => 'Formules'])

@section('content')
    <x-page-header title="Formules d'abonnement" subtitle="Prix, durée et contenu des formules proposées aux vendeurs.">
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouvelle formule</a>
    </x-page-header>

    <div class="card overflow-hidden">
        @if ($plans->isEmpty())
            <x-empty icon="tag" title="Aucune formule" text="Créez au moins une formule pour que les vendeurs puissent s'abonner." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Formule</th><th>Prix</th><th>Durée</th><th>Abonnements</th><th>État</th><th class="text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($plans as $plan)
                            <tr>
                                <td><span class="font-medium text-slate-900">{{ $plan->name }}</span><span class="block text-xs text-slate-500">{{ $plan->description }}</span></td>
                                <td class="font-semibold whitespace-nowrap">{{ fcfa($plan->price) }}</td>
                                <td>{{ $plan->durationLabel() }} <span class="text-xs text-slate-500">({{ $plan->duration_days }} j)</span></td>
                                <td>{{ $plan->subscriptions_count }}</td>
                                <td><x-badge :color="$plan->is_active ? 'green' : 'gray'">{{ $plan->is_active ? 'Proposée' : 'Masquée' }}</x-badge></td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Supprimer la formule « {{ $plan->name }} » ? Les abonnements existants ne sont pas affectés.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-red-600"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
