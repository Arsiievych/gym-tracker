<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_email_is_normalized_on_save(): void
    {
        $user = User::factory()->create(['email' => "  Mixed.Case@EXAMPLE.com\t"]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'mixed.case@example.com',
        ]);
    }

    public function test_user_password_is_stored_as_a_hash(): void
    {
        $password = 'a long test password';

        $user = User::factory()->create(['password' => $password]);
        $storedPassword = $user->refresh()->password;

        $this->assertTrue(Hash::check($password, $storedPassword));
    }

    public function test_user_serialization_hides_the_password(): void
    {
        $user = User::factory()->make();

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_the_database_rejects_duplicate_normalized_emails(): void
    {
        $user = User::factory()->create(['email' => "\t Person@EXAMPLE.COM \n"]);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('users')->insert([
            'name' => 'Another User',
            'email' => 'person@example.com',
            'password' => $user->password,
        ]);
    }
}
