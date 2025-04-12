<?php

namespace App\Jobs;

use App\Mail\OrderCancelledMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailOrderCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        Mail::to($this->order->o_mail)->send(new OrderCancelledMail($this->order));
    }
}
