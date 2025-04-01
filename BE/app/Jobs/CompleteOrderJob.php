<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderStatusFlowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $orderId;
    public function __construct($orderId)
    {
        //
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $order = Order::find($this->orderId);

        // Nếu còn ở trạng thái đã giao thì chuyển qua hoàn thành
        $changed = OrderStatusFlowService::change($order, 'closed', 'system');
    }
}
