<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('name', 255);
            $table->integer('check_interval_seconds')->default(60);
            $table->integer('expected_status_code')->default(200);
            $table->string('expected_keyword', 255)->nullable();
            $table->integer('timeout_seconds')->default(10);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_status')->default('unknown');
            $table->integer('last_http_code')->nullable();
            $table->integer('last_response_ms')->nullable();
            $table->decimal('last_uptime_percent', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
