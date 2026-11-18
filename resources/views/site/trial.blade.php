@extends('layouts.site', ['title' => 'Demander une boutique d\'essai'])

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="text-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-accent-500/10 px-3 py-1 text-sm font-semibold text-accent-600">
                <x-icon name="sparkles" class="size-4" /> {{ \App\Models\Setting::trialDays() }} jours gratuits, sans engagement
            </span>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">Demandez votre boutique d'essai</h1>
            <p class="mt-4 text-lg text-slate-600">Parlez-nous de votre activité. Un conseiller GKAS-GROUP vous rappelle pour préparer votre boutique.</p>
        </div>

        <form method="POST" action="{{ route('site.trial.send') }}" class="card card-body mt-10 space-y-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Votre nom" required autocomplete="name" />
                <x-form.input name="phone" type="tel" label="Téléphone / WhatsApp" required autocomplete="tel" placeholder="ex. +228 90 00 00 00" />
                <x-form.input name="business_name" label="Nom de votre boutique" placeholder="ex. Bella Fashion" />
                <x-form.input name="activity" label="Ce que vous vendez" placeholder="ex. Vêtements femme, accessoires…" />
                <x-form.select name="social_network" label="Réseau social principal" :options="$networks" placeholder="— Choisir —" />
                <x-form.input name="social_handle" label="Votre compte ou lien" placeholder="ex. @bellafashion" />
                <x-form.input name="city" label="Ville" />
                <x-form.input name="email" type="email" label="E-mail (facultatif)" />
            </div>
            <x-form.textarea name="message" label="Précisions (facultatif)" rows="3" />
            <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <p class="text-xs text-slate-500">En envoyant ce formulaire, vous acceptez d'être recontacté par GKAS-GROUP. <a href="{{ route('site.privacy') }}" class="underline">Confidentialité</a></p>
            <button class="btn btn-accent w-full py-3 text-base">Envoyer ma demande</button>
        </form>
    </section>
@endsection
