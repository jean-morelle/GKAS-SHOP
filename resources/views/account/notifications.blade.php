@extends($layout, ['title' => 'Notifications'])

@section('content')
    <x-page-header title="Notifications">
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-secondary"><x-icon name="check" class="size-4" /> Tout marquer comme lu</button>
            </form>
        @endif
    </x-page-header>

    <div class="card overflow-hidden">
        @forelse ($notifications as $n)
            @php
                $level = $n->data['level'] ?? 'info';
                $tone = ['success' => 'bg-emerald-100 text-emerald-600', 'warning' => 'bg-amber-100 text-amber-600', 'danger' => 'bg-red-100 text-red-600'][$level] ?? 'bg-brand-100 text-brand-600';
            @endphp
            <a href="{{ route('notifications.open', $n->id) }}" class="flex items-start gap-4 border-b border-slate-100 px-5 py-4 last:border-0 hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $tone }}"><x-icon name="bell" class="size-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">{{ $n->data['title'] ?? 'Notification' }}</span>
                        @unless ($n->read_at)<span class="size-2 rounded-full bg-brand-600"></span>@endunless
                    </span>
                    <span class="block text-sm text-slate-600">{{ $n->data['message'] ?? '' }}</span>
                    <span class="block text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <x-empty icon="bell" title="Aucune notification" text="Les nouvelles commandes, rappels d'échéance et validations de paiement apparaîtront ici." />
        @endforelse
        {{ $notifications->links() }}
    </div>
@endsection
