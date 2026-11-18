@extends('layouts.auth', ['title' => 'Nouveau mot de passe'])

@section('content')
    <h1 class="text-xl font-bold text-slate-900">Choisir un nouveau mot de passe</h1>

    <div class="mt-5"><x-flash /></div>

    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Adresse e-mail" :value="$email" required />
        <x-form.input name="password" type="password" label="Nouveau mot de passe" required autocomplete="new-password" help="8 caractères minimum, avec des lettres et des chiffres." />
        <x-form.input name="password_confirmation" type="password" label="Confirmation" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-full">Enregistrer</button>
    </form>
@endsection
