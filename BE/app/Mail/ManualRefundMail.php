<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManualRefundMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $order;
    public $transaction;

    public function __construct($order, $transaction)
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->markdown('emails.orders.refund_manual')
            ->subject('Hoàn tiền thủ công đơn hàng #' . $this->order->code);
    }
}
