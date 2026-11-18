{{-- Layout des espaces connectés (administration GKAS et espace vendeur). --}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    // Pages communes (profil, notifications) : un membre de GKAS garde l'habillage administration.
    $isAdminArea = request()->routeIs('admin.*') || ($user->isStaff() && ! request()->routeIs('vendor.*'));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ $platform['platform_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<div class="lg:flex">
    {{-- Barre latérale --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 overflow-y-auto {{ $isAdminArea ? 'bg-slate-900' : 'bg-brand-950' }} px-3 py-5 max-lg:hidden lg:sticky lg:top-0 lg:block lg:h-screen lg:shrink-0">
        <div class="mb-6 flex items-center justify-between px-2">
            <a href="{{ $user->homeRoute() }}" class="flex items-center gap-2.5">@include('partials.brand-logo', ['dark' => true])</a>
            <button type="button" class="text-slate-300 lg:hidden" data-toggle="#sidebar-mobile"><x-icon name="x" /></button>
        </div>
        @if ($isAdminArea)
            <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Administration GKAS</p>
        @endif
        <nav class="space-y-1" aria-label="Navigation de l'espace">
            @yield('nav')
        </nav>
    </aside>

    {{-- Navigation mobile --}}
    <div id="sidebar-mobile" class="fixed inset-0 z-50 lg:hidden" hidden>
        <div class="absolute inset-0 bg-slate-900/60" data-toggle="#sidebar-mobile"></div>
        <aside class="relative h-full w-72 overflow-y-auto {{ $isAdminArea ? 'bg-slate-900' : 'bg-brand-950' }} px-3 py-5">
            <div class="mb-6 flex items-center justify-between px-2">
                <span class="flex items-center gap-2.5">@include('partials.brand-logo', ['dark' => true])</span>
                <button type="button" class="text-slate-300" data-toggle="#sidebar-mobile"><x-icon name="x" /><span class="sr-only">Fermer</span></button>
            </div>
            <nav class="space-y-1">@yield('nav')</nav>
        </aside>
    </div>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn btn-ghost -ml-2 px-2 lg:hidden" data-toggle="#sidebar-mobile" aria-label="Ouvrir le menu">
                <x-icon name="menu" class="size-6" />
            </button>
            <div class="min-w-0 flex-1">@yield('topbar')</div>
            <a href="{{ route('notifications.index') }}" class="relative rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" title="Notifications">
                <x-icon name="bell" class="size-6" />
                @if ($unread)
                    <span class="absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
                <span class="sr-only">{{ $unread }} notification(s) non lue(s)</span>
            </a>
            <div class="relative">
                <button type="button" class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-slate-100" data-toggle="#user-menu" aria-haspopup="true">
                    <span class="flex size-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ $user->name }}</span>
                </button>
                <div id="user-menu" class="absolute right-0 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-2 shadow-lg" hidden>
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }} · {{ $user->role->label() }}</p>
                    </div>
                    <a href="{{ route('account.edit') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="user" class="size-4" /> Mon profil</a>
                    @if ($user->isVendor() && $user->stores()->count() > 1)
                        <p class="px-3 pt-2 text-[11px] font-semibold text-slate-400 uppercase">Mes boutiques</p>
                        @foreach ($user->stores()->orderBy('name')->get() as $s)
                            <a href="{{ route('vendor.dashboard', $s) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="store" class="size-4" /> {{ $s->name }}</a>
                        @endforeach
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"><x-icon name="logout" class="size-4" /> Se déconnecter</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @yield('alerts')
            <div class="mb-6"><x-flash /></div>
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
