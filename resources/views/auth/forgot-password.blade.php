@extends('layouts.auth', ['title' => 'Mot de passe oublié'])

@section('content')
    <h1 class="text-xl font-bold text-slate-900">Mot de passe oublié</h1>
    <p class="mt-1 text-sm text-slate-500">Saisissez votre adresse e-mail : vous recevrez un lien pour choisir un nouveau mot de passe. Vous pouvez aussi demander à GKAS-GROUP de réinitialiser votre accès.</p>

    <div class="mt-5"><x-flash /></div>

    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Adresse e-mail" required autofocus />
        <button type="submit" class="btn btn-primary w-full">Envoyer le lien</button>
    </form>
    <p class="mt-5 text-center text-sm"><a href="{{ route('login') }}" class="text-brand-700 hover:underline">Retour à la connexion</a></p>
@endsection
