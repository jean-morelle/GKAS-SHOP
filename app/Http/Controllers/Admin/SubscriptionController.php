<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->query('vue') === 'historique' ? 'historique' : 'actifs';

        return view('admin.subscriptions.index', [
            'view' => $view,
            'stores' => $view === 'actifs'
                ? Store::subscribed()->with('owner')->orderBy('subscription_ends_at')->paginate(20)->withQueryString()
                : null,
            'subscriptions' => $view === 'historique'
                ? Subscription::with(['store', 'payment', 'creator'])->latest('id')->paginate(25)->withQueryString()
                : null,
        ]);
    }
}
