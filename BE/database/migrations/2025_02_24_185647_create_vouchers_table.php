<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('discount_percent')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('max_discount_amount')->nullable();
            $table->integer('min_product_price')->nullable();
            $table->integer('usage_limit');
            $table->integer('type');
            $table->integer('times_used')->default(0);
            $table->datetime('expiry_date')->nullable();
            $table->datetime('start_date');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
}
