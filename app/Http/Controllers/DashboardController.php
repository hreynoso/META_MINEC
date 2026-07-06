<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                ['label' => 'Usuarios', 'value' => \App\Models\User::count()],
                ['label' => 'Roles', 'value' => \Spatie\Permission\Models\Role::count()],
            ],
        ]);
    }
}
