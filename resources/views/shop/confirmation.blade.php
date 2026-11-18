@extends('layouts.shop', ['title' => 'Commande confirmée'])

@section('content')
    @include('shop._steps', ['step' => 3])

    <div class="rounded-shop mx-auto max-w-2xl border border-black/5 bg-white p-6 text-center sm:p-10">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="size-9" /></span>
        <h1 class="mt-5 text-2xl font-extrabold">Merci {{ $order->customer_name }} !</h1>
        <p class="mt-2 text-slate-600">Votre commande a bien été enregistrée.</p>
        <p class="rounded-shop mx-auto mt-5 inline-block bg-slate-100 px-5 py-2 font-mono text-lg font-bold tracking-wider">{{ $order->reference }}</p>
        <p class="mt-2 text-xs text-slate-500">Conservez ce numéro de commande.</p>

        @if ($whatsappUrl)
            <div class="rounded-shop mt-8 bg-emerald-50 p-5">
                <p class="text-sm text-emerald-900">Pour accélérer le traitement, envoyez le récapitulatif au vendeur sur WhatsApp :</p>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" id="wa-link"
                   class="mt-3 inline-flex items-center gap-2 rounded-shop bg-emerald-500 px-6 py-3 font-semibold text-white hover:bg-emerald-600">
                    <x-icon name="whatsapp" class="size-5" /> Envoyer sur WhatsApp
                </a>
            </div>
            @if ($openWhatsapp)
                <script>setTimeout(() => { window.location.href = document.getElementById('wa-link').href; }, 900);</script>
            @endif
        @endif

        <div class="mt-8 text-left">
            <h2 class="font-bold">Récapitulatif</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-3 py-2"><span>{{ $item->quantity }} × {{ $item->product_name }}</span><span class="whitespace-nowrap">{{ fcfa($item->line_total) }}</span></li>
                @endforeach
            </ul>
            @if ($order->delivery_zone_name)
                <p class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-sm"><span>Livraison ({{ $order->delivery_zone_name }})</span><span>{{ bccomp((string) $order->delivery_fee, '0', 2) > 0 ? fcfa($order->delivery_fee) : 'Gratuit' }}</span></p>
            @endif
            <p class="mt-2 flex justify-between border-t border-slate-200 pt-3 font-bold"><span>Total</span><span>{{ fcfa($order->total) }}</span></p>
            <p class="mt-4 text-sm text-slate-600">Livraison : {{ $order->customer_area }} — {{ $order->delivery_address }}</p>
        </div>

        <p class="mt-8 text-sm text-slate-600">Le vendeur va vous contacter au <strong>{{ $order->customer_phone }}</strong> pour confirmer la commande.</p>
        <a href="{{ shop_route('home', $store) }}" class="btn-shop-outline mt-6">Retour à la boutique</a>
    </div>
@endsection
