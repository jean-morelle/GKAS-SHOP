<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = (string) $request->query('action', '');

        return view('admin.activity', [
            'logs' => ActivityLog::with('user')
                ->when($action !== '', fn ($q) => $q->where('action', 'like', addcslashes($action, '%_\\').'%'))
                ->latest('id')->paginate(30)->withQueryString(),
            'action' => $action,
            'actions' => [
                'store' => 'Boutiques', 'trial' => 'Essais', 'payment' => 'Paiements', 'user' => 'Utilisateurs',
                'plan' => 'Formules', 'prospect' => 'Prospects', 'settings' => 'Paramètres', 'subscription' => 'Abonnements',
            ],
        ]);
    }
}
