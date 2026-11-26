<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\TrainerProfile;
use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AccountRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/auth/register';

    private const PAYLOAD = [
        'email' => 'member@example.com',
        'password' => 'password',
        'profile_type' => 'athlete',
    ];

    #[DataProvider('registrations')]
    public function test_registration_creates_one_profile_and_hashes_the_submitted_password(
        string $email,
        string $storedEmail,
        string $profile,
        string $otherProfile,
        string $password,
    ): void {
        $this->postJson(self::ENDPOINT, [
            'email' => $email,
            'password' => $password,
            'profile_type' => $profile,
        ])->assertNoContent(201);

        $user = User::query()->sole();
        self::assertSame($storedEmail, $user->email);
        self::assertSame(($profile === 'trainer' ? 'Trainer' : 'Athlete').' #'.$user->id, $user->name);
        self::assertNull($user->email_verified_at);
        self::assertSame('argon2id', password_get_info($user->password)['algoName']);
        self::assertNotSame($password, $user->password);
        self::assertTrue(Hash::check($password, $user->password));
        $this->assertDatabaseHas($profile.'_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseCount($otherProfile.'_profiles', 0);
    }

    /** @return array<string, array{string, string, string, string, string}> */
    public static function registrations(): array
    {
        return [
            'athlete with eight characters' => [
                ' Anna.Smith+Gym@EXAMPLE.COM ', 'anna.smith+gym@example.com',
                'athlete', 'trainer', 'абвгдежз',
            ],
            'trainer with spaces in password' => [
                '+_Оля-smith_+@example.com', '+_Оля-smith_+@example.com',
                'trainer', 'athlete', ' password ',
            ],
        ];
    }

    public function test_registration_preserves_a_password_longer_than_72_bytes(): void
    {
        $password = str_repeat('я', 40);

        $this->postJson(self::ENDPOINT, [...self::PAYLOAD, 'password' => $password])->assertNoContent(201);

        $storedHash = User::query()->sole()->password;
        self::assertTrue(Hash::check($password, $storedHash));
        self::assertFalse(Hash::check(str_repeat('я', 36), $storedHash));
    }

    public function test_registration_hashes_a_password_even_when_it_looks_like_a_hash(): void
    {
        $password = Hash::make('other password');

        $this->postJson(self::ENDPOINT, [...self::PAYLOAD, 'password' => $password])
            ->assertNoContent(201);

        $storedHash = User::query()->sole()->password;
        self::assertTrue(Hash::check($password, $storedHash));
        self::assertFalse(Hash::check('other password', $storedHash));
    }

    public function test_registration_requires_all_fields_in_the_json_body(): void
    {
        $this->postJson(self::ENDPOINT.'?'.http_build_query(self::PAYLOAD))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'profile_type']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest('web');
    }

    #[DataProvider('invalidFields')]
    public function test_registration_rejects_invalid_fields(string $field, mixed $value): void
    {
        $this->postJson(self::ENDPOINT, [...self::PAYLOAD, $field => $value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest('web');
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFields(): array
    {
        return [
            'invalid email' => ['email', 'not-an-email'],
            'invalid role' => ['profile_type', 'admin'],
            'array role' => ['profile_type', ['athlete', 'trainer']],
            'seven characters' => ['password', 'абвгдеж'],
            'non-string password' => ['password', 12345678],
            'non-text content' => ['password', ['password']],
        ];
    }

    public function test_duplicate_email_leaves_the_existing_account_untouched(): void
    {
        $storedEmail = 'member@example.com';
        $hash = Hash::make('old password');
        $id = DB::table('users')->insertGetId([
            'email' => $storedEmail,
            'name' => 'Original name',
            'password' => $hash,
        ]);

        $this->postJson(self::ENDPOINT, [...self::PAYLOAD, 'email' => ' Member@EXAMPLE.COM '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $id, 'email' => $storedEmail, 'name' => 'Original name', 'password' => $hash]);
        $this->assertDatabaseCount('athlete_profiles', 0);
        $this->assertDatabaseCount('trainer_profiles', 0);
        $this->assertGuest('web');
    }

    public function test_profile_creation_failure_rolls_back_the_account(): void
    {
        TrainerProfile::creating(static function (): never {
            throw new RuntimeException('Profile creation failed.');
        });

        $this->withoutExceptionHandling();
        $this->assertThrows(
            fn () => $this->postJson(self::ENDPOINT, [...self::PAYLOAD, 'profile_type' => 'trainer']),
            RuntimeException::class,
            'Profile creation failed.',
        );

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('trainer_profiles', 0);
        $this->assertGuest('web');
    }

    public function test_authenticated_users_cannot_register_another_account(): void
    {
        app()->setLocale('en');
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $this->postJson(self::ENDPOINT, self::PAYLOAD)->assertConflict()
            ->assertExactJson(['message' => 'An account is already signed in.']);

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_registration_limits_attempts_per_ip_independently_of_login(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(self::ENDPOINT)->assertUnprocessable();
        }

        $this->postJson(self::ENDPOINT, self::PAYLOAD)->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
        $this->postJson('/api/auth/login')->assertUnprocessable();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11']);
        $this->postJson(self::ENDPOINT, self::PAYLOAD)->assertNoContent(201);
    }
}
