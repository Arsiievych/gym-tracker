<?php

namespace App\Modules\Auth\Actions;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

final class LogoutAction
{
    public function run(Session $session): void
    {
        Auth::guard('web')->logout();

        $session->invalidate();
        $session->regenerateToken();
    }
}
