<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.notifications', $this->areaLayout($request->user()) + [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // N'autorise que les redirections internes.
        return $url && Str::startsWith($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }
}
