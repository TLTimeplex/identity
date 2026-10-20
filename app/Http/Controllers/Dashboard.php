<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Dashboard extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $apps = Application::with('requiredPermission:id,name')->where('is_active', true)->orderBy('name')->get();

        // Filter out what user can access
        $apps = $apps->filter(fn (Application $app) => $app->required_permission === null || $user->can($app->requiredPermission?->name))->values();

        return Inertia::render('Dashboard', [
            'apps' => $apps->map(fn (Application $app) => [
                'id' => $app->id,
                'name' => $app->name,
                'description' => $app->description,
                'icon' => $app->icon,
                'url' => $app->url,
            ]),
        ]);
    }
}
