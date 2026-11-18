<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('position')->orderBy('price')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['is_active' => true, 'duration_days' => 30])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = new Plan($this->validated($request));
        $plan->slug = $this->uniqueSlug($plan->name);
        $plan->save();

        ActivityLogger::log('plan.created', "Formule « {$plan->name} » créée (".fcfa($plan->price)." / {$plan->durationLabel()})", $plan);

        return redirect()->route('admin.plans.index')->with('success', 'Formule créée.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', ['plan' => $plan]);
    }

    /** Les abonnements existants conservent le prix et la durée payés (instantané). */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $before = $plan->only(['price', 'duration_days', 'is_active']);
        $plan->fill($this->validated($request))->save();

        ActivityLogger::log('plan.updated', "Formule « {$plan->name} » modifiée", $plan, [
            'before' => $before,
            'after' => $plan->only(['price', 'duration_days', 'is_active']),
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Formule mise à jour.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete(); // suppression logique : l'historique reste lisible
        ActivityLogger::log('plan.deleted', "Formule « {$plan->name} » supprimée", $plan);

        return redirect()->route('admin.plans.index')->with('success', 'Formule supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:1095'],
            'features' => ['nullable', 'string', 'max:3000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [], ['name' => 'nom', 'price' => 'prix', 'duration_days' => 'durée']);

        $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features'] ?? '')))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
        $data['is_active'] = $request->boolean('is_active');
        $data['position'] ??= 0;

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'formule';
        $slug = $base;
        $i = 2;
        while (Plan::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
