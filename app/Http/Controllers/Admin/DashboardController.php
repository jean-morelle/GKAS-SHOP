<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ProspectStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'stores' => Store::count(),
            'trial' => Store::onTrial()->count(),
            'subscribed' => Store::subscribed()->count(),
            'expired' => Store::expired()->count(),
            'suspended' => Store::whereIn('status', [StoreStatus::Suspended, StoreStatus::Archived])->count(),
            'orders' => Order::where('is_demo', false)->count(),
            'demo_orders' => Order::where('is_demo', true)->count(),
            'leads' => DemoRequest::count(),
            'new_leads' => DemoRequest::where('status', ProspectStatus::New)->count(),
            'pending_payments' => Payment::where('status', PaymentStatus::Pending)->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'expiringSoon' => Store::onTrial()->where('trial_ends_at', '<=', now()->addDays(7))->orderBy('trial_ends_at')->take(6)->get(),
            'pendingPayments' => Payment::with(['store', 'plan'])->where('status', PaymentStatus::Pending)->oldest()->take(6)->get(),
            'followUps' => DemoRequest::with('assignee')->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now()->addDays(2))
                ->whereNotIn('status', [ProspectStatus::Converted, ProspectStatus::Lost])
                ->orderBy('next_follow_up_at')->take(6)->get(),
            'activities' => ActivityLog::with('user')->latest('id')->take(8)->get(),
        ]);
    }
}
