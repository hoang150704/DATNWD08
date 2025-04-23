<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->unsignedBigInteger('main_image')->nullable();
            $table->foreign('main_image')->references('id')->on('libraries')->onDelete('set null');
            // $table->unsignedBigInteger('box_id');
            // $table->foreign('box_id')->references('id')->on('boxes');
            $table->boolean('is_active')->default(true)->comment('1: hiển thị, khác 1: ẩn');
            $table->string('slug', 255);
            $table->enum('type', [0, 1]);
            $table->double('avg_rating')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_products');
    }
};
