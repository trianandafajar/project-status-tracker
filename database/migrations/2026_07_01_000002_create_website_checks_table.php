<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->integer('http_status_code');
            $table->integer('response_time_ms');
            $table->integer('ssl_days_remaining')->nullable();
            $table->string('ssl_status', 20)->nullable();
            $table->boolean('is_up');
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');

            $table->index(['website_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_checks');
    }
};
