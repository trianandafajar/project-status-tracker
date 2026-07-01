<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('viewer')->after('password');
            $table->string('timezone', 100)->default('UTC')->after('role');
            $table->json('preferences')->nullable()->after('timezone');
            $table->string('avatar_url', 500)->nullable()->after('preferences');
            $table->timestamp('last_login_at')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'timezone', 'preferences', 'avatar_url', 'last_login_at']);
        });
    }
};
