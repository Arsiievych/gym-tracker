<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Data\LoginData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

final class LoginAction
{
    public function run(#[SensitiveParameter] LoginData $data): void
    {
        $authenticated = Auth::guard('web')->attempt([
            'email' => $data->email,
            'password' => $data->password,
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
    }
}
