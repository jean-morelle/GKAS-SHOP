<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\SubscriptionMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        return redirect($request->user()->homeRoute());
    }

    public function show(Store $store, SubscriptionMonitor $monitor): View
    {
        $this->authorize('manage', $store);

        // Contrôle à la demande des échéances (au cas où le planificateur ne tourne pas).
        $monitor->check($store);

        $orders = $store->orders();
        $stats = [
            'products' => $store->products()->count(),
            'available' => $store->products()->visible()
                ->where(fn ($q) => $q->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))->count(),
            'orders' => (clone $orders)->count(),
            'to_process' => (clone $orders)->whereIn('status', OrderStatus::toProcess())->count(),
            'in_progress' => (clone $orders)->whereIn('status', OrderStatus::inProgress())->count(),
            'delivered' => (clone $orders)->where('status', OrderStatus::Delivered)->count(),
            'sales_total' => (string) ((clone $orders)->countedAsSale()->sum('total') ?: '0'),
            'sales_month' => (string) ((clone $orders)->countedAsSale()->where('created_at', '>=', now()->startOfMonth())->sum('total') ?: '0'),
        ];

        return view('seller.dashboard', [
            'store' => $store,
            'stats' => $stats,
            'latestOrders' => $store->orders()->latest()->take(6)->get(),
            'pendingPayment' => $store->payments()->where('status', 'pending')->latest()->first(),
        ]);
    }
}
