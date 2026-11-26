<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\ViewModels\AccountViewModel;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

final class CurrentAccountController
{
    public function show(#[CurrentUser] Authenticatable $user, AccountViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->run($user));
    }
}
