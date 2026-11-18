<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Store;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'plans' => Plan::available()->take(3)->get(),
            'demoStores' => $this->demoStores()->take(2),
        ]);
    }

    public function features(): View
    {
        return view('site.features');
    }

    public function how(): View
    {
        return view('site.how');
    }

    public function demo(): View
    {
        return view('site.demo', ['demoStores' => $this->demoStores()]);
    }

    public function pricing(): View
    {
        return view('site.pricing', ['plans' => Plan::available()->get()]);
    }

    public function faq(): View
    {
        return view('site.faq');
    }

    public function terms(): View
    {
        return view('site.legal', ['title' => 'Conditions d\'utilisation', 'key' => 'terms']);
    }

    public function privacy(): View
    {
        return view('site.legal', ['title' => 'Politique de confidentialité', 'key' => 'privacy']);
    }

    private function demoStores()
    {
        return Store::query()->where('is_demo', true)->where('is_published', true)
            ->operational()->withCount(['products' => fn ($q) => $q->visible()])->orderBy('name')->get();
    }
}
