<?php
namespace App\Jobs;

use App\Mail\VerifyGuestOrderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVerifyGuestOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $orderCode;
    public $otp;

    public function __construct($email, $orderCode, $otp)
    {
        $this->email = $email;
        $this->orderCode = $orderCode;
        $this->otp = $otp;
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new VerifyGuestOrderMail($this->orderCode, $this->otp));
    }
}
