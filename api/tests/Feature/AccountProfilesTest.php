<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\ProfileRole;
use App\Modules\Auth\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_reflect_profiles_added_to_the_same_account(): void
    {
        $user = User::factory()->create();

        $this->assertSame([], $user->roles());

        $user->trainerProfile()->create();

        $this->assertSame([ProfileRole::Trainer], $user->roles());

        $user->athleteProfile()->create();

        $this->assertSame(
            [ProfileRole::Athlete, ProfileRole::Trainer],
            array_map(static fn (ProfileRole $role): string => $role->value, $user->roles()),
        );
    }

    public function test_both_profiles_resolve_their_owner(): void
    {
        $user = User::factory()->create();

        $trainer = $user->trainerProfile()->create();
        $athlete = $user->athleteProfile()->create();

        $this->assertTrue($trainer->user?->is($user));
        $this->assertTrue($athlete->user?->is($user));
    }

    public function test_deleting_an_account_cascades_to_both_profiles(): void
    {
        $user = User::factory()->create();
        $user->trainerProfile()->create();
        $user->athleteProfile()->create();

        DB::table('users')->where('id', $user->id)->delete();

        $this->assertDatabaseMissing('trainer_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('athlete_profiles', ['user_id' => $user->id]);
    }

    #[DataProvider('profileTables')]
    public function test_the_database_allows_one_profile_per_type_and_owner(string $table): void
    {
        $user = User::factory()->create();
        $profile = ['user_id' => $user->id];
        DB::table($table)->insert($profile);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table($table)->insert($profile);
    }

    #[DataProvider('profileTables')]
    public function test_the_database_requires_an_explicit_profile_owner(string $table): void
    {
        // A SQLite INTEGER PRIMARY KEY would assign ID 1 to the first profile.
        User::factory()->create(['id' => 1]);

        $this->assertThrows(
            fn () => DB::table($table)->insert(['created_at' => now()]),
            QueryException::class,
            'user_id',
        );
    }

    /** @return array<string, array{string}> */
    public static function profileTables(): array
    {
        return [
            'athlete' => ['athlete_profiles'],
            'trainer' => ['trainer_profiles'],
        ];
    }
}
