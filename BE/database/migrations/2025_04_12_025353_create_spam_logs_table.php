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
        Schema::create('spam_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); 
            $table->ipAddress('ip');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('data')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spam_logs');
    }
};
