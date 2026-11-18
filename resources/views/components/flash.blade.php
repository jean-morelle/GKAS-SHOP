@php
    $messages = [
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'check-circle'],
        'error' => ['bg-red-50 border-red-200 text-red-800', 'warning'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-900', 'warning'],
        'info' => ['bg-blue-50 border-blue-200 text-blue-800', 'info'],
    ];
@endphp
<div class="space-y-3">
    @foreach ($messages as $key => [$classes, $icon])
        @if (session($key))
            <div class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
                <x-icon :name="$icon" class="mt-0.5 size-5 shrink-0" />
                <p>{{ session($key) }}</p>
            </div>
        @endif
    @endforeach

    @if ($errors->any() && ! ($hideErrors ?? false))
        <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <x-icon name="warning" class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">Veuillez corriger les points suivants :</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('credentials'))
        <div class="rounded-lg border-2 border-dashed border-amber-400 bg-amber-50 px-4 py-4 text-sm text-amber-950" role="status">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="lock" class="size-5" /> Identifiants temporaires — affichés une seule fois</p>
            <p class="mt-2">Communiquez-les au titulaire du compte par un canal sûr. Le mot de passe devra être changé à la première connexion.</p>
            <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                <div class="rounded bg-white px-3 py-2"><dt class="text-xs text-slate-500">E-mail</dt><dd class="font-mono">{{ session('credentials.email') }}</dd></div>
                <div class="rounded bg-white px-3 py-2"><dt class="text-xs text-slate-500">Mot de passe temporaire</dt><dd class="font-mono select-all">{{ session('credentials.password') }}</dd></div>
            </dl>
        </div>
    @endif
</div>
