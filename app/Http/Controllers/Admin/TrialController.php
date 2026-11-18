<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\TrialHistory;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrialController extends Controller
{
    public function index(Request $request): View
    {
        $tab = in_array($request->query('vue'), ['bientot', 'expires', 'historique'], true) ? $request->query('vue') : 'en-cours';

        $query = match ($tab) {
            'bientot' => Store::onTrial()->where('trial_ends_at', '<=', now()->addDays(7))->orderBy('trial_ends_at'),
            'expires' => Store::expired()->orderByDesc('trial_ends_at'),
            default => Store::onTrial()->orderBy('trial_ends_at'),
        };

        return view('admin.trials.index', [
            'tab' => $tab,
            'stores' => $tab === 'historique' ? null : $query->with('owner')->paginate(20)->withQueryString(),
            'histories' => $tab === 'historique' ? TrialHistory::with(['store', 'performer'])->latest('id')->paginate(25) : null,
            'counts' => [
                'en-cours' => Store::onTrial()->count(),
                'bientot' => Store::onTrial()->where('trial_ends_at', '<=', now()->addDays(7))->count(),
                'expires' => Store::expired()->count(),
            ],
        ]);
    }

    public function extend(Request $request, Store $store, StoreService $stores): RedirectResponse
    {
        // Prolongation réservée aux administrateurs (policy), motif obligatoire.
        $this->authorize('extendTrial', $store);

        if ($store->hasActiveSubscription()) {
            return back()->with('error', 'Cette boutique a un abonnement actif : la prolongation d\'essai est inutile.');
        }

        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['days' => 'nombre de jours', 'reason' => 'motif']);

        $stores->extendTrial($store, (int) $data['days'], $data['reason'], $request->user());

        return back()->with('success', "Essai prolongé de {$data['days']} jour(s), jusqu'au {$store->fresh()->trial_ends_at->format('d/m/Y à H:i')}.");
    }
}
