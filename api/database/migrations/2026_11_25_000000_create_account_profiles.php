<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['athlete_profiles', 'trainer_profiles'] as $table) {
            Schema::create($table, function (Blueprint $profile): void {
                $profile->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
                $profile->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_profiles');
        Schema::dropIfExists('athlete_profiles');
    }
};
