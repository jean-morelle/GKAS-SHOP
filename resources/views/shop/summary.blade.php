@extends('layouts.shop', ['title' => 'Vérification de la commande'])

@section('content')
    @include('shop._steps', ['step' => 2])

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-shop border border-black/5 bg-white p-5 lg:col-span-2">
            <h1 class="text-xl font-extrabold">Vérifiez votre commande</h1>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase">
                        <tr><th class="py-2">Produit</th><th class="py-2 text-center">Qté</th><th class="py-2 text-right">Prix unitaire</th><th class="py-2 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cart['lines'] as $line)
                            <tr>
                                <td class="py-2.5 font-medium">{{ $line->product->name }}</td>
                                <td class="py-2.5 text-center">{{ $line->quantity }}</td>
                                <td class="py-2.5 text-right whitespace-nowrap">{{ fcfa($line->unit_price) }}</td>
                                <td class="py-2.5 text-right font-semibold whitespace-nowrap">{{ fcfa($line->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if ($delivery['zone'])
                            <tr class="border-t border-slate-200"><td colspan="3" class="pt-3">Sous-total</td><td class="pt-3 text-right whitespace-nowrap">{{ fcfa($cart['total']) }}</td></tr>
                            <tr>
                                <td colspan="3" class="py-1">Livraison — {{ $delivery['zone']->name }} @if ($delivery['free'])<span class="text-xs font-semibold text-emerald-600">(offerte)</span>@endif</td>
                                <td class="py-1 text-right whitespace-nowrap">{{ bccomp($delivery['fee'], '0', 2) > 0 ? fcfa($delivery['fee']) : 'Gratuit' }}</td>
                            </tr>
                        @endif
                        <tr class="border-t-2 border-slate-200 text-base"><td colspan="3" class="pt-3 font-bold">Total à régler</td><td class="pt-3 text-right font-extrabold whitespace-nowrap">{{ fcfa($total) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="rounded-shop mt-6 bg-slate-50 p-4 text-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Livraison</h2>
                    <a href="{{ shop_route('checkout', $store) }}" class="underline">Modifier</a>
                </div>
                <dl class="mt-2 grid gap-1 sm:grid-cols-2">
                    <div><dt class="inline text-slate-500">Nom :</dt> <dd class="inline">{{ $customer['name'] }}</dd></div>
                    <div><dt class="inline text-slate-500">Téléphone :</dt> <dd class="inline">{{ $customer['phone'] }}</dd></div>
                    <div><dt class="inline text-slate-500">Quartier :</dt> <dd class="inline">{{ $customer['area'] }}</dd></div>
                    <div class="sm:col-span-2"><dt class="inline text-slate-500">Adresse :</dt> <dd class="inline">{{ $customer['address'] }}</dd></div>
                    @if (! empty($customer['notes']))<div class="sm:col-span-2"><dt class="inline text-slate-500">Notes :</dt> <dd class="inline">{{ $customer['notes'] }}</dd></div>@endif
                </dl>
            </div>
        </section>

        <aside class="rounded-shop h-fit border border-black/5 bg-white p-5">
            <h2 class="font-bold">Valider la commande</h2>
            <p class="mt-2 text-sm text-slate-600">Aucun paiement en ligne : le vendeur vous contacte pour confirmer la commande et convenir du paiement et de la livraison.</p>
            <form method="POST" action="{{ shop_route('checkout.place', $store) }}" class="mt-5 space-y-3" data-once>
                @csrf
                @if ($store->whatsappNumber())
                    <button name="channel" value="whatsapp" class="flex w-full items-center justify-center gap-2 rounded-shop bg-emerald-500 px-4 py-3 font-semibold text-white hover:bg-emerald-600">
                        <x-icon name="whatsapp" class="size-5" /> Valider et envoyer sur WhatsApp
                    </button>
                @endif
                <button name="channel" value="web" class="{{ $store->whatsappNumber() ? 'btn-shop-outline' : 'btn-shop' }} w-full">
                    <x-icon name="check" class="size-5" /> Valider la commande
                </button>
            </form>
        </aside>
    </div>
@endsection
