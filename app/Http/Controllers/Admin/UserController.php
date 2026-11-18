<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): View
    {
        $role = UserRole::tryFrom((string) $request->query('role', 'vendor'));
        $search = trim((string) $request->query('q', ''));

        return view('admin.users.index', [
            'users' => User::query()->withCount('stores')
                ->when($role, fn ($q) => $q->where('role', $role))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('phone', 'like', '%'.addcslashes($search, '%_\\').'%')))
                ->orderBy('name')->paginate(20)->withQueryString(),
            'role' => $role,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => UserRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        [$user, $password] = $this->users->create($data, UserRole::from($data['role']));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Compte créé.')
            ->with('credentials', ['email' => $user->email, 'password' => $password]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', ['user' => $user->load('stores')]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));

        if ($user->is($request->user()) && $data['role'] !== $user->role->value) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle.');
        }

        $user->fill($request->only('name', 'email', 'phone'))->save();
        $this->users->changeRole($user, UserRole::from($data['role']));

        return redirect()->route('admin.users.show', $user)->with('success', 'Compte mis à jour.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $this->users->setActive($user, ! $user->is_active);

        return back()->with('success', $user->is_active ? 'Compte réactivé.' : 'Compte désactivé : l\'utilisateur ne peut plus se connecter.');
    }

    public function resetAccess(User $user): RedirectResponse
    {
        $password = $this->users->resetAccess($user);

        return back()
            ->with('success', 'Nouveau mot de passe temporaire généré. Il devra être changé à la prochaine connexion.')
            ->with('credentials', ['email' => $user->email, 'password' => $password]);
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }
}
