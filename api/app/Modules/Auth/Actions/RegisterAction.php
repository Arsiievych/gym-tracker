<?php

namespace App\Modules\Auth\Actions;

use App\Exceptions\BusinessException;
use App\Modules\Auth\Data\RegisterData;
use App\Modules\Auth\Repositories\AccountRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

final class RegisterAction
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function run(#[SensitiveParameter] RegisterData $data): void
    {
        $guard = Auth::guard('web');
        if ($guard->check()) {
            throw new BusinessException(__('auth.already_authenticated'), 409);
        }

        $id = $this->createAccount($data);

        if ($guard->loginUsingId($id) === false) {
            throw new BusinessException(__('auth.registration_login_failed'), 500);
        }
    }

    private function createAccount(#[SensitiveParameter] RegisterData $data): int
    {
        $passwordHash = Hash::make($data->password);

        try {
            return DB::transaction(function () use ($data, $passwordHash): int {
                $id = $this->accounts->create($data->email, $passwordHash);
                $this->accounts->updateName($id, $data->profileType->label().' #'.$id);
                $this->accounts->createProfile($id, $data->profileType);

                return $id;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($exception->index !== 'users_email_unique') {
                throw $exception;
            }

            throw ValidationException::withMessages(['email' => __('auth.email_taken')]);
        }
    }
}
