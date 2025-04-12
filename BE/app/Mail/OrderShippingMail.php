<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $order;
    public $shipment;
     public function __construct($order, $shipment)
     {
         $this->order = $order;
         $this->shipment = $shipment;
     }
 
     public function build()
     {
         return $this->markdown('emails.orders.shipping')
             ->subject('Đơn hàng #' . $this->order->code . ' đang được giao');
     }
}
