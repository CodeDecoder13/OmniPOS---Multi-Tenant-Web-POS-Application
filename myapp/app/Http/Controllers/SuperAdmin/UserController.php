<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }
}
