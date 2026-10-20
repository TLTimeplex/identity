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
use Spatie\Permission\Models\Permission;

class ApplicationController extends Controller
{
    public function index(): Response
    {
        $applications = Application::with(['category:id,name', 'requiredPermission:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->id,
                'name' => $application->name,
                'description' => $application->description,
                'url' => $application->url,
                'icon' => $application->icon,
                'is_active' => $application->is_active,
                'category' => $application->category?->name,
                'required_permission' => $application->requiredPermission?->name,
                'created_at' => $application->created_at?->toIso8601String(),
                'updated_at' => $application->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function show(Application $application): Response
    {
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
            ...$this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Applications/Create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $application = new Application;
        $this->persist($application, $this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application created.')]);

        return to_route('admin_applications_show', $application);
    }

    public function createCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('application_category', 'name')],
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
        $this->persist($application, $this->validated($request, $application));

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

    /**
     * @return array<string, array<int, mixed>>
     */
    private function formOptions(): array
    {
        return [
            'allCategories' => ApplicationCategory::query()->orderBy('name')->get(['id', 'name'])->map->only(['id', 'name'])->all(),
            'allPermissions' => Permission::query()->orderBy('name')->pluck('name')->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Application $application = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'category' => ['nullable', 'string', Rule::exists('application_category', 'name')],
            'required_permission' => ['nullable', 'string', Rule::exists('permissions', 'name')],
            'hydra_client_id' => ['nullable', 'string', 'max:255', Rule::unique('application', 'hydra_client_id')->ignore($application?->id)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Application $application, array $data): void
    {
        $application->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => $data['is_active'],
            'category_id' => ! empty($data['category'])
                ? ApplicationCategory::where('name', $data['category'])->value('id')
                : null,
            'required_permission' => ! empty($data['required_permission'])
                ? Permission::where('name', $data['required_permission'])->value('id')
                : null,
            'hydra_client_id' => $data['hydra_client_id'] ?? null,
        ])->save();
    }
}
