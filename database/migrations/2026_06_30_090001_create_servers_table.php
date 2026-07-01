<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('host', 255);
            $table->integer('port')->default(22);
            $table->string('username', 255);
            $table->string('auth_type')->default('password');
            $table->text('auth_key')->nullable();
            $table->string('connection_type')->default('ssh');
            $table->string('status')->default('offline');
            $table->integer('health_score')->default(100);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('os')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
