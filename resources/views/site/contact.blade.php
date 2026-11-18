@extends('layouts.site', ['title' => 'Contact'])

@section('content')
    <section class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-5 lg:px-8">
        <div class="lg:col-span-2">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Contacter {{ $platform['company_name'] }}</h1>
            <p class="mt-4 text-slate-600">Une question sur GKAS Shop, un besoin particulier ? Écrivez-nous, nous vous répondons rapidement.</p>
            <ul class="mt-8 space-y-4 text-slate-700">
                @if ($platform['contact_whatsapp'])
                    <li><a href="https://wa.me/{{ preg_replace('/\D+/', '', $platform['contact_whatsapp']) }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-emerald-600"><x-icon name="whatsapp" class="size-6 text-emerald-500" /> {{ $platform['contact_whatsapp'] }}</a></li>
                @endif
                @if ($platform['contact_phone'])
                    <li class="flex items-center gap-3"><x-icon name="phone" class="size-6 text-brand-600" /> {{ $platform['contact_phone'] }}</li>
                @endif
                @if ($platform['contact_email'])
                    <li class="flex items-center gap-3"><x-icon name="chat" class="size-6 text-brand-600" /> {{ $platform['contact_email'] }}</li>
                @endif
                @if ($platform['contact_address'])
                    <li class="flex items-center gap-3"><x-icon name="map-pin" class="size-6 text-brand-600" /> {{ $platform['contact_address'] }}</li>
                @endif
            </ul>
            <div class="mt-8 rounded-xl bg-accent-500/10 p-5">
                <p class="font-semibold text-slate-900">Vous voulez une boutique ?</p>
                <p class="mt-1 text-sm text-slate-600">Utilisez plutôt le formulaire dédié : nous préparons votre essai gratuit.</p>
                <a href="{{ route('site.trial') }}" class="btn btn-accent btn-sm mt-3">Demander une boutique d'essai</a>
            </div>
        </div>

        <form method="POST" action="{{ route('site.contact.send') }}" class="card card-body space-y-4 lg:col-span-3">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Votre nom" required />
                <x-form.input name="phone" type="tel" label="Téléphone / WhatsApp" required />
            </div>
            <x-form.input name="email" type="email" label="E-mail (facultatif)" />
            <x-form.textarea name="message" label="Votre message" rows="6" required />
            <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <p class="text-xs text-slate-500">Vos coordonnées servent uniquement à vous recontacter. <a href="{{ route('site.privacy') }}" class="underline">Confidentialité</a></p>
            <button class="btn btn-primary w-full sm:w-auto">Envoyer le message</button>
        </form>
    </section>
@endsection
