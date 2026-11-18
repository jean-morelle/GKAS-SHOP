<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccessState;
use App\Enums\ProspectStatus;
use App\Enums\StoreStatus;
use App\Enums\StoreTheme;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormRequest;
use App\Models\DemoRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ImageUploader;
use App\Services\StoreService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreService $stores,
        private readonly ImageUploader $uploader,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $access = AccessState::tryFrom((string) $request->query('acces', ''));

        $stores = Store::query()->with('owner')->withCount(['products', 'orders'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('slug', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhereHas('owner', fn ($q) => $q->where('email', 'like', '%'.addcslashes($search, '%_\\').'%'))))
            ->when($access === AccessState::Trial, fn ($q) => $q->onTrial())
            ->when($access === AccessState::Subscribed, fn ($q) => $q->subscribed())
            ->when($access === AccessState::Expired, fn ($q) => $q->expired())
            ->when($access === AccessState::Suspended, fn ($q) => $q->where('status', StoreStatus::Suspended))
            ->when($access === AccessState::Archived, fn ($q) => $q->where('status', StoreStatus::Archived))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.stores.index', compact('stores', 'search', 'access'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Store::class);

        $prospect = $request->integer('prospect') ? DemoRequest::find($request->integer('prospect')) : null;

        return view('admin.stores.form', [
            'store' => new Store([
                'name' => $prospect?->business_name ?: '',
                'whatsapp' => $prospect?->phone,
                'phone' => $prospect?->phone,
                'primary_color' => '#4f46e5',
                'theme' => StoreTheme::Moderne,
                'is_published' => true,
            ]),
            'vendors' => User::where('role', UserRole::Vendor)->orderBy('name')->get(),
            'themes' => StoreTheme::cases(),
            'prospect' => $prospect,
        ]);
    }

    public function store(StoreFormRequest $request, UserService $users): RedirectResponse
    {
        [$store, $password, $owner] = DB::transaction(function () use ($request, $users) {
            $password = null;

            if ($request->input('owner_mode') === 'new') {
                [$owner, $password] = $users->create([
                    'name' => $request->input('owner_name'),
                    'email' => $request->input('owner_email'),
                    'phone' => $request->input('owner_phone'),
                ], UserRole::Vendor);
            } else {
                $owner = User::query()->where('role', UserRole::Vendor)->findOrFail($request->integer('owner_id'));
            }

            $store = $this->stores->create($request->storeData(), $owner, $request->input('slug'), $request->boolean('is_demo'));
            $this->saveBranding($request, $store);

            if ($prospectId = $request->integer('prospect_id')) {
                $prospect = DemoRequest::find($prospectId);
                $prospect->store_id = $store->id;
                $prospect->status = ProspectStatus::TrialCreated;
                $prospect->save();
            }

            return [$store, $password, $owner];
        });

        $redirect = redirect()->route('admin.stores.show', $store)
            ->with('success', "Boutique « {$store->name} » créée. Essai gratuit jusqu'au {$store->trial_ends_at->format('d/m/Y')}.");

        // Le mot de passe temporaire n'est affiché qu'une seule fois.
        return $password ? $redirect->with('credentials', ['email' => $owner->email, 'password' => $password]) : $redirect;
    }

    public function show(Store $store): View
    {
        $this->authorize('view', $store);

        $store->load(['owner', 'users', 'deliveryZones']);

        return view('admin.stores.show', [
            'store' => $store,
            'products' => $store->products()->with('category')->latest('id')->take(10)->get(),
            'productCount' => $store->products()->count(),
            'orders' => $store->orders()->latest()->take(10)->get(),
            'orderCount' => $store->orders()->count(),
            'payments' => $store->payments()->with('plan')->latest()->get(),
            'subscriptions' => $store->subscriptions()->with('creator')->latest('ends_at')->get(),
            'trialHistories' => $store->trialHistories()->with('performer')->latest('id')->get(),
            'vendors' => User::where('role', UserRole::Vendor)->orderBy('name')->get(),
            'statuses' => StoreStatus::cases(),
        ]);
    }

    public function edit(Store $store): View
    {
        $this->authorize('update', $store);

        return view('admin.stores.form', [
            'store' => $store,
            'vendors' => collect(),
            'themes' => StoreTheme::cases(),
            'prospect' => null,
        ]);
    }

    public function update(StoreFormRequest $request, Store $store): RedirectResponse
    {
        $this->stores->update($store, $request->storeData(), $request->input('slug'));
        $store->is_demo = $request->boolean('is_demo');
        $this->saveBranding($request, $store);

        return redirect()->route('admin.stores.show', $store)->with('success', 'Boutique mise à jour.');
    }

    public function status(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('changeStatus', $store);

        $data = $request->validate([
            'status' => ['required', Rule::enum(StoreStatus::class)],
            'reason' => ['required_unless:status,active', 'nullable', 'string', 'max:500'],
        ], ['reason.required_unless' => 'Indiquez le motif de la suspension ou de l\'archivage.']);

        $this->stores->changeStatus($store, StoreStatus::from($data['status']), $data['reason'] ?? null);

        return back()->with('success', 'Statut de la boutique : '.StoreStatus::from($data['status'])->label().'.');
    }

    public function owner(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('changeStatus', $store);

        $data = $request->validate([
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Vendor->value)->whereNull('deleted_at')],
        ]);

        $this->stores->changeOwner($store, User::findOrFail($data['owner_id']));

        return back()->with('success', 'Propriétaire mis à jour.');
    }

    /**
     * Domaine personnalisé (ex. bellafashion.com). Le DNS du domaine doit pointer
     * vers le serveur GKAS Shop ; la plateforme sert alors la boutique sur ce domaine.
     */
    public function domain(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('changeStatus', $store);

        $request->merge(['custom_domain' => $this->normalizeDomain((string) $request->input('custom_domain'))]);
        $data = $request->validate([
            'custom_domain' => [
                'nullable', 'string', 'max:190',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/',
                Rule::notIn(config('gkas.platform_domains')),
                Rule::unique('stores', 'custom_domain')->ignore($store->id),
            ],
        ], [
            'custom_domain.regex' => 'Saisissez un nom de domaine valide, par exemple bellafashion.com.',
            'custom_domain.not_in' => 'Ce domaine est celui de la plateforme.',
            'custom_domain.unique' => 'Ce domaine est déjà attribué à une autre boutique.',
        ], ['custom_domain' => 'domaine']);

        $previous = $store->custom_domain;
        $store->custom_domain = $data['custom_domain'] ?: null;
        $store->save();

        ActivityLogger::log('store.domain_changed', "Domaine de « {$store->name} » : ".($previous ?: 'aucun').' → '.($store->custom_domain ?: 'aucun'), $store);

        return back()->with('success', $store->custom_domain
            ? "Domaine {$store->custom_domain} attribué. Vérifiez que son DNS pointe vers le serveur."
            : 'Domaine personnalisé retiré.');
    }

    /** « https://www.BellaFashion.com/ » → « www.bellafashion.com » */
    private function normalizeDomain(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('#^[a-z]+://#', '', $value);

        return rtrim(explode('/', $value)[0], '.');
    }

    private function saveBranding(Request $request, Store $store): void
    {
        foreach (['logo' => 'logo_path', 'banner' => 'banner_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $store->{$column} = $this->uploader->replace($store->{$column}, $request->file($input), "stores/{$store->id}/branding");
            }
        }

        $store->save();
    }
}
