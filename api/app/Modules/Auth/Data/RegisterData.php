<?php

namespace App\Modules\Auth\Data;

use SensitiveParameter;

final readonly class RegisterData
{
    public function __construct(
        public string $email,
        #[SensitiveParameter] public string $password,
        public ProfileRole $profileType,
    ) {}
}
