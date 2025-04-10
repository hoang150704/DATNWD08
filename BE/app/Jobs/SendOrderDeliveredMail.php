<?php

namespace App\Jobs;

use App\Mail\OrderDeliveredMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderDeliveredMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $order;
    public function __construct(Order $order)
    {
        // 
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->order->o_mail)->send(new OrderDeliveredMail($this->order));
    }
}
