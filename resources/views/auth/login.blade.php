@extends('layouts.auth', ['title' => 'Connexion'])

@section('content')
    <h1 class="text-xl font-bold text-slate-900">Connexion à votre espace</h1>
    <p class="mt-1 text-sm text-slate-500">Vendeurs et équipe GKAS-GROUP.</p>

    <div class="mt-5"><x-flash /></div>

    <form method="POST" action="{{ route('login.attempt') }}" class="mt-5 space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Adresse e-mail" required autofocus autocomplete="email" />
        <x-form.input name="password" type="password" label="Mot de passe" required autocomplete="current-password" />
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-brand-600"> Se souvenir de moi
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:underline">Mot de passe oublié ?</a>
        </div>
        <button type="submit" class="btn btn-primary w-full">Se connecter</button>
    </form>

    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
        Pas encore de boutique ? <a href="{{ route('site.trial') }}" class="font-semibold text-accent-600 hover:underline">Demandez votre essai gratuit</a>
    </p>
@endsection
