<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request, Store $store): View
    {
        $this->authorize('manageOrders', $store);

        $search = trim((string) $request->query('q', ''));
        $status = OrderStatus::tryFrom((string) $request->query('statut', ''));
        $group = (string) $request->query('groupe', '');

        $orders = $store->orders()->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('customer_name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('customer_phone', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($group === 'a-traiter', fn ($q) => $q->whereIn('status', OrderStatus::toProcess()))
            ->when($group === 'en-cours', fn ($q) => $q->whereIn('status', OrderStatus::inProgress()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('seller.orders.index', compact('store', 'orders', 'search', 'status', 'group'));
    }

    public function show(Store $store, Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['items', 'statusHistories.user']);

        return view('seller.orders.show', [
            'store' => $store,
            'order' => $order,
            'transitions' => $order->status->allowedTransitions(),
            'customerWhatsapp' => $this->customerWhatsappUrl($store, $order),
        ]);
    }

    public function status(Request $request, Store $store, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->changeStatus($order, OrderStatus::from($data['status']), $data['note'] ?? null, $request->user());

        return back()->with('success', 'Statut mis à jour : '.OrderStatus::from($data['status'])->label().'.');
    }

    public function notes(Request $request, Store $store, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate(['vendor_notes' => ['nullable', 'string', 'max:2000']]);
        $order->forceFill(['vendor_notes' => $data['vendor_notes']])->save();

        return back()->with('success', 'Notes enregistrées.');
    }

    public function markPaid(Request $request, Store $store, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        if ($order->status === OrderStatus::Cancelled) {
            return back()->with('error', 'Une commande annulée ne peut pas être marquée comme payée.');
        }

        $order->forceFill(['paid_at' => $order->paid_at ? null : now()])->save();

        return back()->with('success', $order->paid_at ? 'Commande marquée comme payée.' : 'Paiement de la commande annulé.');
    }

    private function customerWhatsappUrl(Store $store, Order $order): ?string
    {
        $number = $order->whatsappCustomerNumber();

        if (strlen($number) < 8) {
            return null;
        }

        $text = "Bonjour {$order->customer_name}, ici {$store->name}. Votre commande {$order->reference} est : {$order->status->label()}.";

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }
}
