<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyGuestOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $orderCode;
    public $otp;

    public function __construct(string $orderCode, string $otp)
    {
        $this->orderCode = $orderCode;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Xác thực đơn hàng #' . $this->orderCode)
            ->markdown('emails.verify_guest_order')
            ->with([
                'orderCode' => $this->orderCode,
                'otp' => $this->otp,
            ]);
    }
}
