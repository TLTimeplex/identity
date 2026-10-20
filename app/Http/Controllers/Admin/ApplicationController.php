<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = Application::with('category:name')->get(['name', 'description', 'url', 'icon', 'is_active', 'created_at', 'updated_at']);

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function show(Application $application): Response
    {
        $lifetime = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        return Inertia::render('Admin/Applications/Show', [
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'description' => $application->description,
                'url' => $application->url,
                'icon' => $application->icon,
                'is_active' => $application->is_active,
                'created_at' => $application->created_at?->toIso8601String(),
                'updated_at' => $application->updated_at?->toIso8601String(),
                'category' => $application->category?->name,
                'required_permission' => $application->requiredPermission?->name,
                'hydra_client_id' => $application->hydra_client_id,
            ],
            'allCategories' => ApplicationCategory::query()->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Applications/Create', [
            'allCategories' => ApplicationCategory::query()->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function createCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('application_categories', 'name')],
        ]);

        ApplicationCategory::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application category created.')]);

        return back();
    }

    public function deleteCategory(Request $request, ApplicationCategory $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application category deleted.')]);

        return back();
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'category' => ['nullable', 'string', Rule::exists('application_categories', 'name')],
            'required_permission' => ['nullable', 'string', Rule::exists('permissions', 'name')],
            'hydra_client_id' => ['nullable', 'string', 'max:255'],
        ]);

        $application->fill([
            'name' => $data['name'],
            'description' => $data['description'],
            'url' => $data['url'],
            'icon' => $data['icon'],
            'is_active' => $data['is_active'],
            'required_permission' => $data['required_permission'] ?? null,
            'hydra_client_id' => $data['hydra_client_id'] ?? null,
        ]);

        if ($data['category']) {
            $category = ApplicationCategory::where('name', $data['category'])->first();
            $application->category()->associate($category);
        } else {
            $application->category()->dissociate();
        }

        $application->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return back();
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        DB::transaction(function () use ($application): void {
            $application->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('admin_applications_index');
    }
}
