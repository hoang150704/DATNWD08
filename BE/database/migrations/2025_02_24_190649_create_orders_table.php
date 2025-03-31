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
            $table->string('code', 255);
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
            $table->string('shipping_code')->nullable(); // mã đơn hàng của GHN
            $table->foreignId('shipping_status_id')->constrained('shipping_statuses')->default(1);
            $table->decimal('shipping_fee', 10, 2)->nullable(); // tổng phí GHN
            $table->string('carrier')->default('ghn');

            $table->timestamp('expected_delivery_time')->nullable(); // thời gian giao dự kiến
            $table->timestamp('from_estimate_date')->nullable(); // khoảng dự kiến (nếu có)
            $table->timestamp('to_estimate_date')->nullable();
            $table->timestamp('actual_delivery_date')->nullable(); // giao thành công lúc
            $table->timestamp('pickup_time')->nullable(); // lấy hàng lúc

            $table->string('sort_code')->nullable(); // tuyến phân loại
            $table->string('transport_type')->nullable(); // truck / bike / ...

            $table->json('shipping_fee_details')->nullable(); // các loại phí chi tiết
            $table->boolean('return_confirmed')->default(false);
            $table->timestamp('return_confirmed_at')->nullable();
            $table->text('cancel_reason')->nullable(); // nếu bị hủy
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
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->json('images')->nullable(); // ảnh sản phẩm lỗi
        
            // Thêm 3 trường thông tin ngân hàng
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
        
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamps();
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

            $table->enum('method', ['vnpay', 'ship_cod']); // phương thức thanh toán
            $table->enum('type', ['payment', 'refund']);   // thanh toán hay hoàn tiền

            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed']);

            // Dùng chung
            $table->string('transaction_code')->nullable();     // vnp_TxnRef hoặc mã CK
            $table->text('note')->nullable();

            // Dành cho VNPAY
            $table->string('vnp_transaction_no')->nullable();
            $table->string('vnp_bank_code')->nullable();
            $table->string('vnp_bank_tran_no')->nullable();
            $table->timestamp('vnp_pay_date')->nullable();
            $table->string('vnp_card_type')->nullable();
            $table->string('vnp_response_code')->nullable();
            $table->string('vnp_transaction_status')->nullable();
            $table->timestamp('vnp_create_date')->nullable();
            $table->string('vnp_refund_request_id')->nullable();

            // Dành cho hoàn tiền thủ công (ship_cod)
            $table->string('transfer_reference')->nullable();  // mã giao dịch ngân hàng
            $table->json('proof_images')->nullable();          // ảnh chụp minh chứng

            $table->timestamps();
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
