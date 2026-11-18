@extends('layouts.site', ['title' => 'Tarifs'])

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-center text-4xl font-extrabold tracking-tight text-slate-900">Tarifs</h1>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-slate-600">
            Essayez gratuitement pendant {{ \App\Models\Setting::trialDays() }} jours. Ensuite, choisissez la formule qui vous convient.
        </p>

        @if ($plans->isEmpty())
            <div class="card mx-auto mt-12 max-w-xl">
                <x-empty icon="tag" title="Tarifs sur demande" text="Contactez-nous pour connaître nos formules adaptées à votre activité.">
                    <a href="{{ route('site.contact') }}" class="btn btn-primary">Nous contacter</a>
                </x-empty>
            </div>
        @else
            <div @class(['mt-12 grid gap-6 md:grid-cols-2', 'lg:grid-cols-3' => $plans->count() >= 3, 'mx-auto max-w-3xl' => $plans->count() < 3])>
                @foreach ($plans as $plan)
                    @include('site._plan-card', ['plan' => $plan, 'highlight' => $plans->count() >= 3 && $loop->iteration === 2])
                @endforeach
            </div>
        @endif

        <div class="mx-auto mt-14 max-w-3xl space-y-3 text-sm text-slate-600">
            <p class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-emerald-500" /> Les prix sont indiqués en FCFA. Aucun paiement n'est demandé pendant l'essai.</p>
            <p class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-emerald-500" /> Paiement par Mobile Money, virement ou au bureau GKAS-GROUP. L'abonnement est activé après vérification.</p>
            <p class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-emerald-500" /> Sans renouvellement, votre boutique est mise en pause et vos données sont conservées.</p>
        </div>
    </section>
@endsection
