<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\LeadRequest;
use App\Models\DemoRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProspectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = ProspectStatus::tryFrom((string) $request->query('statut', ''));
        $mine = $request->boolean('mes-prospects');

        $prospects = DemoRequest::with(['assignee', 'store'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('business_name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('phone', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($mine, fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.prospects.index', [
            'prospects' => $prospects,
            'search' => $search,
            'status' => $status,
            'mine' => $mine,
            'counts' => DemoRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.prospects.create', ['networks' => DemoRequest::socialNetworks()]);
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $prospect = new DemoRequest($request->safe()->except('website'));
        $prospect->type = 'manual';
        $prospect->status = ProspectStatus::New;
        $prospect->assigned_to = $request->user()->id;
        $prospect->save();

        return redirect()->route('admin.prospects.show', $prospect)->with('success', 'Prospect enregistré.');
    }

    public function show(DemoRequest $prospect): View
    {
        return view('admin.prospects.show', [
            'prospect' => $prospect->load(['notes.user', 'assignee', 'store']),
            'staff' => User::whereIn('role', [UserRole::Admin, UserRole::Sales])->where('is_active', true)->orderBy('name')->get(),
            'statuses' => ProspectStatus::cases(),
        ]);
    }

    /** Mise à jour du suivi : étape, commercial affecté, date de relance. */
    public function update(Request $request, DemoRequest $prospect): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ProspectStatus::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', [UserRole::Admin->value, UserRole::Sales->value])],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $previous = $prospect->status;
        $prospect->status = ProspectStatus::from($data['status']);
        $prospect->assigned_to = $data['assigned_to'] ?? null;
        $prospect->next_follow_up_at = $data['next_follow_up_at'] ?? null;
        $prospect->save();

        if ($previous !== $prospect->status) {
            ActivityLogger::log('prospect.status', "Prospect {$prospect->name} : {$previous->label()} → {$prospect->status->label()}", $prospect);
        }

        return back()->with('success', 'Suivi commercial mis à jour.');
    }

    public function note(Request $request, DemoRequest $prospect): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'note']);

        $note = $prospect->notes()->make($data);
        $note->user_id = $request->user()->id;
        $note->save();

        return back()->with('success', 'Note ajoutée.');
    }
}
