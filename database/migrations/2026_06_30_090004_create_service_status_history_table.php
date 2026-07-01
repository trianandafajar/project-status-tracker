<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('output')->nullable();
            $table->dateTime('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_status_history');
    }
};
