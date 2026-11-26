<?php

namespace App\Modules\Auth\Http\ViewModels;

use App\Modules\Auth\Data\AccountData;
use App\Modules\Auth\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;

final readonly class AccountViewModel
{
    public function run(Authenticatable $user): AccountData
    {
        if (! $user instanceof User) {
            throw new LogicException('The authenticated account must be a user.');
        }

        return new AccountData(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            roles: $user->roles(),
        );
    }
}
