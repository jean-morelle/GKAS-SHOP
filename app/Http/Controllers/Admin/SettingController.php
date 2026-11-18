<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Setting::allValues()]);
    }

    public function update(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:80'],
            'company_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'currency' => ['required', 'string', 'max:10'],
            'payment_instructions' => ['nullable', 'string', 'max:3000'],
            'terms' => ['nullable', 'string', 'max:20000'],
            'privacy' => ['nullable', 'string', 'max:20000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ], [], ['trial_days' => 'durée d\'essai', 'platform_name' => 'nom de la plateforme']);

        $before = Setting::allValues();

        if ($request->hasFile('logo')) {
            $data['platform_logo'] = $uploader->replace($before['platform_logo'] ?? null, $request->file('logo'), 'platform');
        }
        unset($data['logo']);

        Setting::put($data);

        $changed = collect($data)->filter(fn ($v, $k) => ($before[$k] ?? null) != $v)->keys()->all();
        ActivityLogger::log('settings.updated', 'Paramètres de la plateforme modifiés', null, ['fields' => $changed]);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
