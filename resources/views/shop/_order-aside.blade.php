<aside class="rounded-shop h-fit border border-black/5 bg-white p-5">
    <div class="flex items-center justify-between">
        <h2 class="font-bold">Votre panier</h2>
        <a href="{{ shop_route('cart', $store) }}" class="text-sm underline">Modifier</a>
    </div>
    <ul class="mt-4 divide-y divide-slate-100 text-sm">
        @foreach ($cart['lines'] as $line)
            <li class="flex justify-between gap-3 py-2">
                <span>{{ $line->quantity }} × {{ $line->product->name }}</span>
                <span class="font-medium whitespace-nowrap">{{ fcfa($line->line_total) }}</span>
            </li>
        @endforeach
    </ul>
    <p class="mt-3 flex justify-between border-t border-slate-200 pt-3 text-base font-bold"><span>Total</span><span>{{ fcfa($cart['total']) }}</span></p>
    @if ($store->order_conditions)
        <p class="mt-3 text-xs whitespace-pre-line text-slate-500">{{ $store->order_conditions }}</p>
    @endif
</aside>
