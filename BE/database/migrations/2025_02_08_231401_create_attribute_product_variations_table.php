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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade'); 
            $table->string('sku',255);
            $table->unsignedBigInteger('variant_image')->nullable();
            $table->foreign('variant_image')->references('id')->on('libraries')->onDelete('set null'); // Thêm ràng buộc khóa ngoại
            $table->integer('regular_price')->nullable();
            $table->integer('sale_price')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_product_variations');
    }
};
