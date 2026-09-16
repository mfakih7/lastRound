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
        Schema::create('client_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('purchased_sessions');
            $table->unsignedInteger('used_sessions')->default(0);
            $table->decimal('price_paid', 10, 2);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index('status');
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_packages');
    }
};
