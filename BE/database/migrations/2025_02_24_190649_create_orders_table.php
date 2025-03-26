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
        // Trạng thái đơn hàng
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); 
        });

        // Trạng thái thanh toán
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
        });

        // Trạng thái vận chuyển
        Schema::create('shipping_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
        });
        //Đơn hàng
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->char('code');
            $table->bigInteger('total_amount');
            $table->bigInteger('discount_amount');
            $table->bigInteger('final_amount');
            $table->enum('payment_method', ['ship_cod', 'vnpay']);
            $table->bigInteger('shipping')->nullable();
            $table->text('o_name');
            $table->text('o_address');
            $table->text('o_phone');
            $table->text('o_mail')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('order_status_id')->constrained('order_statuses');
            $table->foreignId('payment_status_id')->constrained('payment_statuses');
            $table->foreignId('shipping_status_id')->constrained('shipping_statuses');
            $table->text('cancel_reason')->nullable();
            $table->string('cancel_by')->nullable(); 
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        // Thông tin GHN
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('shipping_code')->nullable(); // mã đơn hàng của giao hàng nhanh
            $table->foreignId('shipping_status_id')->constrained('shipping_statuses')->default(1); // trạng thái GHN hiện tại
            $table->decimal('shipping_fee', 10, 2)->nullable(); // phí giao hàng
            $table->string('carrier')->default('ghn'); // đơn vị vận chuyển
            $table->timestamp('from_estimate_date')->nullable(); // từ 
            $table->timestamp('to_estimate_date')->nullable(); // đến ngày (giao hàng dự kiến)
            $table->timestamp('actual_delivery_date')->nullable(); // ngày giao hàng thực tế
            $table->timestamp('pickup_time')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps(); 
        });
        //
         // Lịch sử đơn hàng GHN
         Schema::create('shipping_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->string('ghn_status');
            $table->foreignId('mapped_status_id')->constrained('shipping_statuses');
            $table->string('location')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('timestamp')->nullable();
        });

        // Xử lí hoàn tiền
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('type', ['not_received', 'return_after_received']);
            $table->text('reason')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
        });

        // Lịch sử đơn hàng hệ thông
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('from_status_id')->nullable()->constrained('order_statuses');
            $table->foreignId('to_status_id')->constrained('order_statuses');
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });

        // Lịch sử đơn hàng
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('method', ['vnpay', 'ship_cod']);
            $table->enum('type', ['payment', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code')->nullable(); // vnp_TxnRef or CK code
            $table->string('vnp_transaction_no')->nullable();
            $table->string('vnp_bank_code')->nullable();
            $table->timestamp('vnp_pay_date')->nullable();
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('shipping_logs');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_statuses');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('order_statuses');
    }
};
