<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\StoreRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Équipe d'une boutique : le propriétaire ajoute des gérants et des employés.
 */
class TeamController extends Controller
{
    public function index(Store $store): View
    {
        $this->authorize('manageTeam', $store);

        return view('seller.team', [
            'store' => $store,
            'members' => $store->users()->orderByRaw("CASE store_user.role WHEN 'owner' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")->orderBy('name')->get(),
            'roles' => StoreRole::assignable(),
        ]);
    }

    public function store(Request $request, Store $store, UserService $users): RedirectResponse
    {
        $this->authorize('manageTeam', $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, StoreRole::assignable()))],
        ], [], ['name' => 'nom', 'email' => 'e-mail', 'role' => 'rôle']);

        $role = StoreRole::from($data['role']);
        $existing = User::withTrashed()->where('email', $data['email'])->first();

        if ($existing && ($existing->trashed() || ! $existing->isVendor() || ! $existing->is_active)) {
            return back()->withInput()->withErrors(['email' => 'Cette adresse ne peut pas être ajoutée à l\'équipe. Contactez GKAS-GROUP.']);
        }

        if ($existing && $existing->belongsToStore($store)) {
            return back()->withInput()->withErrors(['email' => 'Cette personne fait déjà partie de l\'équipe.']);
        }

        $password = DB::transaction(function () use ($existing, $data, $role, $store, $users) {
            $password = null;
            $member = $existing;

            if (! $member) {
                [$member, $password] = $users->create($data, UserRole::Vendor);
            }

            $store->users()->attach($member->id, ['role' => $role->value]);
            ActivityLogger::log('store.member_added', "{$member->email} ajouté à « {$store->name} » ({$role->label()})", $store, ['role' => $role->value]);

            return $password;
        });

        $redirect = back()->with('success', "{$data['name']} a été ajouté(e) à l'équipe en tant que {$role->label()}.");

        // Nouveau compte : mot de passe temporaire affiché une seule fois.
        return $password ? $redirect->with('credentials', ['email' => $data['email'], 'password' => $password]) : $redirect;
    }

    public function update(Request $request, Store $store, User $member): RedirectResponse
    {
        $this->authorize('manageTeam', $store);
        $this->ensureEditable($request, $store, $member);

        $data = $request->validate(['role' => ['required', Rule::in(array_map(fn ($r) => $r->value, StoreRole::assignable()))]]);
        $previous = $member->storeRole($store);
        $role = StoreRole::from($data['role']);

        $store->users()->updateExistingPivot($member->id, ['role' => $role->value]);
        ActivityLogger::log('store.member_role', "Rôle de {$member->email} dans « {$store->name} » : {$previous?->label()} → {$role->label()}", $store);

        return back()->with('success', "{$member->name} est désormais {$role->label()}.");
    }

    public function destroy(Request $request, Store $store, User $member): RedirectResponse
    {
        $this->authorize('manageTeam', $store);
        $this->ensureEditable($request, $store, $member);

        $store->users()->detach($member->id);
        ActivityLogger::log('store.member_removed', "{$member->email} retiré de « {$store->name} »", $store);

        return back()->with('success', "{$member->name} n'a plus accès à la boutique.");
    }

    /** Le propriétaire et soi-même ne sont pas modifiables ici. */
    private function ensureEditable(Request $request, Store $store, User $member): void
    {
        abort_if($member->is($request->user()), 403, 'Vous ne pouvez pas modifier votre propre accès.');
        abort_if($member->storeRole($store) === StoreRole::Owner, 403, 'Le propriétaire de la boutique ne peut être changé que par GKAS-GROUP.');
    }
}
