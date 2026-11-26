<?php

namespace App\Modules\Auth\Data;

use BackedEnum;
use JsonSerializable;

final readonly class AccountData implements JsonSerializable
{
    /** @param list<BackedEnum> $roles */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public array $roles,
    ) {}

    /** @return array{id: int, name: string, email: string, roles: list<BackedEnum>} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
