<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->createNormalizedEmailIndex();

            foreach (['athlete_profiles', 'trainer_profiles'] as $table) {
                Schema::create($table, function (Blueprint $profile): void {
                    // BIGINT prevents SQLite from generating the owner's ID.
                    $profile->rawColumn('user_id', 'BIGINT PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE');
                    $profile->timestamps();
                });
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            Schema::dropIfExists('trainer_profiles');
            Schema::dropIfExists('athlete_profiles');
            DB::statement('DROP INDEX IF EXISTS users_normalized_email_unique');
        });
    }

    private function createNormalizedEmailIndex(): void
    {
        // Match the model's ASCII case folding and whitespace trimming.
        $whitespace = " \t\n\r\v\f";
        $trimmedEmail = DB::connection()->getDriverName() === 'pgsql'
            ? "btrim(email, '$whitespace') COLLATE \"C\""
            : "trim(email, '$whitespace')";

        DB::statement("CREATE UNIQUE INDEX users_normalized_email_unique ON users (lower($trimmedEmail))");
    }
};
