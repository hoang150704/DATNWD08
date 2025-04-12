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
        Schema::create('spam_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'ip' hoặc 'user'
            $table->string('value'); // giá trị tương ứng (IP hoặc user_id)
            $table->text('reason')->nullable(); // lý do bị ban
            $table->timestamp('until')->nullable(); // null = ban vĩnh viễn
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spam_blacklists');
    }
};
