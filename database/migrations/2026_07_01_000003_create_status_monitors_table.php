<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('group_name', 120)->default('General');
            $table->string('type', 30)->default('website');
            $table->string('url', 500)->unique();
            $table->string('method', 10)->default('HEAD');
            $table->integer('expected_status_code')->default(200);
            $table->string('expected_keyword', 255)->nullable();
            $table->text('request_body_template')->nullable();
            $table->integer('timeout_seconds')->default(10);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_status', 20)->default('unknown');
            $table->integer('last_http_code')->nullable();
            $table->integer('last_response_ms')->nullable();
            $table->decimal('last_uptime_percent', 5, 2)->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->index(['enabled', 'group_name']);
            $table->index(['enabled', 'type']);
            $table->index(['enabled', 'last_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_monitors');
    }
};
