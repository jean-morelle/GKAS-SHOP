@extends('layouts.site', ['title' => 'Questions fréquentes'])

@section('content')
    @php $days = \App\Models\Setting::trialDays(); @endphp
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-center text-4xl font-extrabold tracking-tight text-slate-900">Questions fréquentes</h1>

        <div class="mt-12 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
            @foreach ([
                ['Faut-il savoir programmer ?', 'Non. GKAS-GROUP crée votre boutique avec vous. Ensuite, vous ajoutez vos produits et suivez vos commandes depuis un espace simple, utilisable sur téléphone.'],
                ["L'essai est-il vraiment gratuit ?", "Oui. Pendant {$days} jours, vous utilisez votre boutique sans rien payer. Aucun paiement automatique n'est déclenché à la fin de l'essai."],
                ['Que se passe-t-il à la fin de l\'essai ?', 'Si vous choisissez une formule et que votre paiement est vérifié, votre boutique continue normalement. Sinon, elle cesse d\'accepter de nouvelles commandes, mais vos données sont conservées.'],
                ['Mes clients doivent-ils créer un compte ?', 'Non. Ils choisissent leurs produits, indiquent leur nom, téléphone, quartier et instructions de livraison, puis valident.'],
                ['Comment mes clients paient-ils ?', 'Vous restez maître de l\'encaissement : paiement à la livraison, Mobile Money… Vous confirmez chaque commande avec votre client. GKAS Shop ne prélève rien sur vos ventes.'],
                ['Puis-je recevoir les commandes sur WhatsApp ?', 'Oui. Le client peut envoyer un récapitulatif de sa commande sur votre WhatsApp en un clic. La commande est aussi enregistrée dans votre espace.'],
                ['Comment payer mon abonnement ?', 'Depuis votre espace, choisissez une formule, suivez les instructions de paiement, puis indiquez la référence de la transaction. GKAS-GROUP vérifie et active votre abonnement.'],
                ['Puis-je récupérer mes données ?', 'Oui, vous pouvez exporter vos produits et vos commandes à tout moment, même si votre abonnement a expiré.'],
                ['Puis-je avoir plusieurs boutiques ?', 'Oui, un même compte vendeur peut gérer plusieurs boutiques. Contactez-nous.'],
            ] as [$q, $a])
                <details class="group p-5 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-slate-900">
                        {{ $q }}
                        <x-icon name="plus" class="size-5 shrink-0 text-slate-400 transition group-open:rotate-45" />
                    </summary>
                    <p class="mt-3 text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>

        <p class="mt-10 text-center text-slate-600">Une autre question ? <a href="{{ route('site.contact') }}" class="font-semibold text-brand-700 hover:underline">Contactez-nous</a></p>
    </section>
@endsection
