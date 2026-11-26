<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\Data\LoginData;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->json()->all();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function credentials(): LoginData
    {
        $credentials = $this->safe();

        return new LoginData(
            email: strtolower($credentials->string('email')->toString()),
            password: $credentials->string('password')->toString(),
        );
    }
}
