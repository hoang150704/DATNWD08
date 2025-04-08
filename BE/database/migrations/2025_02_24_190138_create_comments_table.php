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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('product_id');
            // $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            // $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            // $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_mail')->nullable(); 
            $table->tinyInteger('rating')->unsigned();
            $table->text('content');
            $table->json('images')->nullable(); 
            $table->text('reply')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('hidden_reason')->nullable();
            $table->boolean('is_updated')->default(false);
            $table->timestamp('reply_at');
            $table->timestamps();
    

        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
