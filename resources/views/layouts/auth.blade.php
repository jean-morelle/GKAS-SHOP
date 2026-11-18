<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Connexion' }} — {{ $platform['platform_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5">@include('partials.brand-logo', ['dark' => true])</a>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
            @yield('content')
        </div>
        <a href="{{ route('home') }}" class="mt-6 text-sm text-brand-200 hover:text-white">← Retour au site</a>
    </div>
</body>
</html>
