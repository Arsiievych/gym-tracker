<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Data\ProfileRole;
use App\Modules\Auth\Models\User;
use SensitiveParameter;

final class AccountRepository
{
    public function create(string $email, #[SensitiveParameter] string $passwordHash): int
    {
        return User::query()->create([
            'email' => $email,
            'name' => '',
            'password' => $passwordHash,
        ])->id;
    }

    public function updateName(int $userId, string $name): void
    {
        User::query()
            ->whereKey($userId)
            ->update(['name' => $name]);
    }

    public function createProfile(int $userId, ProfileRole $profileType): void
    {
        $user = User::query()->findOrFail($userId);

        match ($profileType) {
            ProfileRole::Athlete => $user->athleteProfile()->create(),
            ProfileRole::Trainer => $user->trainerProfile()->create(),
        };
    }
}
