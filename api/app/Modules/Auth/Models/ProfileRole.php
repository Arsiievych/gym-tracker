<?php

namespace App\Modules\Auth\Models;

enum ProfileRole: string
{
    case Athlete = 'athlete';
    case Trainer = 'trainer';
}
