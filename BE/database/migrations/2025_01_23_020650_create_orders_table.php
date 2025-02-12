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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->char('code');
            $table->float('total_amount');
            $table->float('discount_amount');
            $table->float('final_amount');
            $table->enum('payment_method',['ship_cod','bank_transfer','e-wallets']);
            $table->float('shipping')->nullable();
            $table->text('o_name');
            $table->text('o_address');
            $table->text('o_phone');
            $table->text('o_mail')->nullable();
            $table->integer('stt_track');
            $table->integer('stt_payment');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
