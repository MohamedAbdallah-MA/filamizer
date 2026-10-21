<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's own appearance: only the keys that differ from the admin's defaults.
        Schema::create('studio_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->json('data');
            $table->timestamps();
        });

        // Saved looks. user_id null = shared by the admin with everyone.
        Schema::create('studio_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name');
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_profiles');
        Schema::dropIfExists('studio_user_preferences');
    }
};
