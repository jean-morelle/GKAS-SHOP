<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\PaymentSubmitRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Store;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $status = PaymentStatus::tryFrom((string) $request->query('statut', 'pending'));

        return view('admin.payments.index', [
            'payments' => Payment::with(['store', 'plan', 'submitter'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => Payment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['store', 'plan', 'submitter', 'reviewer', 'subscription']);

        return view('admin.payments.show', ['payment' => $payment]);
    }

    public function proof(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    /** Enregistrement d'un paiement reçu directement par GKAS-GROUP (espèces, virement…). */
    public function create(Store $store): View
    {
        $this->authorize('recordPayment', $store);

        return view('admin.payments.create', [
            'store' => $store,
            'plans' => Plan::available()->get(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(PaymentSubmitRequest $request, Store $store): RedirectResponse
    {
        $this->authorize('recordPayment', $store);

        $payment = $this->subscriptions->submitPayment(
            $store,
            Plan::query()->findOrFail($request->integer('plan_id')),
            PaymentMethod::from($request->input('method')),
            $request->input('transaction_reference'),
            $request->user(),
            $request->file('proof'),
            $request->input('payer_note'),
        );

        return redirect()->route('admin.payments.show', $payment)
            ->with('success', 'Paiement enregistré. Vérifiez-le puis confirmez-le pour activer l\'abonnement.');
    }

    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('review', $payment);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $subscription = $this->subscriptions->confirm($payment, $request->user(), $data['note'] ?? null);

        return back()->with('success', "Paiement confirmé. Abonnement actif jusqu'au {$subscription->ends_at->format('d/m/Y')}.");
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('review', $payment);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'motif du rejet']);
        $this->subscriptions->reject($payment, $request->user(), $data['reason']);

        return back()->with('success', 'Paiement rejeté. Le vendeur a été notifié.');
    }
}
