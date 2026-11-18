@extends('admin._layout', ['title' => 'Journal d\'activité'])

@section('content')
    <x-page-header title="Journal d'activité" subtitle="Opérations sensibles réalisées sur la plateforme." />

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('admin.activity.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => ! $action, 'bg-white text-slate-600 ring-1 ring-slate-200' => $action])>Tout</a>
        @foreach ($actions as $key => $label)
            <a href="{{ route('admin.activity.index', ['action' => $key]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $action === $key, 'bg-white text-slate-600 ring-1 ring-slate-200' => $action !== $key])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($logs->isEmpty())
            <x-empty icon="list" title="Aucune activité" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Auteur</th><th>Action</th><th>Description</th><th class="max-lg:hidden">IP</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->user?->name ?? 'Système' }}</td>
                                <td><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $log->action }}</code></td>
                                <td>
                                    {{ $log->description }}
                                    @if ($log->properties)
                                        <details class="mt-1 text-xs text-slate-500"><summary class="cursor-pointer">Détails</summary><pre class="mt-1 overflow-x-auto rounded bg-slate-50 p-2">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
                                    @endif
                                </td>
                                <td class="max-lg:hidden text-xs text-slate-500">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        @endif
    </div>
@endsection
