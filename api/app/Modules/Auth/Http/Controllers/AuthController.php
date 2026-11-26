<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\LogoutAction;
use App\Modules\Auth\Actions\RegisterAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController
{
    public function register(RegisterRequest $request, RegisterAction $action): Response
    {
        $action->run($request->registration());

        return response()->noContent(201);
    }

    public function login(LoginRequest $request, LoginAction $action): Response
    {
        $action->run($request->credentials());

        return response()->noContent();
    }

    public function logout(Request $request, LogoutAction $action): Response
    {
        $action->run($request->session());

        return response()->noContent();
    }
}
