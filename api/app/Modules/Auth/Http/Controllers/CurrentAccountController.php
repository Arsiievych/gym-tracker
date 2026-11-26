<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Data\AccountData;
use App\Modules\Auth\Http\ViewModels\AccountViewModel;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Authenticatable;

final class CurrentAccountController
{
    public function show(#[CurrentUser] Authenticatable $user, AccountViewModel $viewModel): AccountData
    {
        return $viewModel->run($user);
    }
}
