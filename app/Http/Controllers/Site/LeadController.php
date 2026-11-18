<?php

namespace App\Http\Controllers\Site;

use App\Enums\ProspectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\LeadRequest;
use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function contact(): View
    {
        return view('site.contact');
    }

    public function trial(): View
    {
        return view('site.trial', ['networks' => DemoRequest::socialNetworks()]);
    }

    public function storeContact(LeadRequest $request): RedirectResponse
    {
        $this->save($request, 'contact');

        return back()->with('success', 'Merci ! Votre message a bien été reçu. L\'équipe GKAS-GROUP vous recontacte rapidement.');
    }

    public function storeTrial(LeadRequest $request): RedirectResponse
    {
        $this->save($request, 'demo');

        return back()->with('success', 'Merci ! Votre demande de boutique d\'essai est enregistrée. Un conseiller GKAS-GROUP vous appelle très bientôt.');
    }

    private function save(LeadRequest $request, string $type): void
    {
        // Champ piège anti-robot : rempli → on ignore silencieusement.
        if ($request->filled('website')) {
            return;
        }

        $lead = new DemoRequest($request->safe()->except('website'));
        $lead->type = $type;
        $lead->status = ProspectStatus::New;
        $lead->save();
    }
}
