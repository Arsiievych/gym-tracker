<?php

namespace App\Modules\Auth\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return HasOne<AthleteProfile, $this> */
    public function athleteProfile(): HasOne
    {
        return $this->hasOne(AthleteProfile::class);
    }

    /** @return HasOne<TrainerProfile, $this> */
    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    /** @return list<ProfileRole> */
    public function roles(): array
    {
        $roles = [];

        if ($this->athleteProfile()->exists()) {
            $roles[] = ProfileRole::Athlete;
        }

        if ($this->trainerProfile()->exists()) {
            $roles[] = ProfileRole::Trainer;
        }

        return $roles;
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email, " \t\n\r\v\f"));
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::set(self::normalizeEmail(...));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
