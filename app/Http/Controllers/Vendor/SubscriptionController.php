<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\PaymentSubmitRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Store;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Store $store): View
    {
        $this->authorize('manageBilling', $store);

        return view('seller.subscription', [
            'store' => $store,
            'plans' => Plan::available()->get(),
            'methods' => PaymentMethod::cases(),
            'payments' => $store->payments()->with('plan')->latest()->get(),
            'subscriptions' => $store->subscriptions()->latest('ends_at')->get(),
            'instructions' => Setting::get('payment_instructions'),
            'hasPending' => $store->payments()->where('status', 'pending')->exists(),
        ]);
    }

    public function pay(PaymentSubmitRequest $request, Store $store): RedirectResponse
    {
        if ($store->payments()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Un paiement est déjà en attente de vérification. Patientez ou annulez-le avant d\'en soumettre un nouveau.');
        }

        $this->subscriptions->submitPayment(
            $store,
            Plan::query()->findOrFail($request->integer('plan_id')),
            PaymentMethod::from($request->input('method')),
            $request->input('transaction_reference'),
            $request->user(),
            $request->file('proof'),
            $request->input('payer_note'),
        );

        return back()->with('success', 'Paiement envoyé. GKAS-GROUP va le vérifier ; votre abonnement sera activé dès sa confirmation.');
    }

    public function cancel(Request $request, Store $store, Payment $payment): RedirectResponse
    {
        $this->authorize('cancel', $payment);

        $this->subscriptions->cancel($payment, $request->user());

        return back()->with('success', 'Demande de paiement annulée.');
    }
}
