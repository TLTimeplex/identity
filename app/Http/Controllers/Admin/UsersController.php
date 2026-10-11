<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsersController extends Controller
{
  public function index(Request $request)
  {
    $users = User::with('roles:name')->get(['name', 'email', 'id', 'oidc_subject', 'created_at', 'updated_at']);


    return Inertia::render('Admin/Users/Index', [
        'users' => $users,
    ]);
  }
}
