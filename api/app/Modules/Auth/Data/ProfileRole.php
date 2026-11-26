<?php

namespace App\Modules\Auth\Data;

enum ProfileRole: string
{
    case Athlete = 'athlete';
    case Trainer = 'trainer';

    public function label(): string
    {
        return match ($this) {
            self::Athlete => 'Athlete',
            self::Trainer => 'Trainer',
        };
    }
}
