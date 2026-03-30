<?php

namespace App\Listeners;

use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $request = request();
        $user = $event->user;

        UserLogin::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenants?->first()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
