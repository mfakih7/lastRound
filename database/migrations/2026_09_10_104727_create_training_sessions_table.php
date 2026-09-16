<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('client_package_id')->nullable()->constrained('client_packages')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('session_date');
            $table->index(['coach_user_id', 'session_date']);
            $table->index(['client_id', 'session_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
