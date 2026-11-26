<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\Data\ProfileRole;
use App\Modules\Auth\Data\RegisterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class RegisterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->json()->all();
    }

    /** @return array<string, list<string|Enum>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'profile_type' => ['required', 'string', Rule::enum(ProfileRole::class)],
        ];
    }

    public function registration(): RegisterData
    {
        $registration = $this->safe();

        return new RegisterData(
            email: $registration->string('email')->toString(),
            password: $registration->string('password')->toString(),
            profileType: ProfileRole::from($registration->string('profile_type')->toString()),
        );
    }
}
