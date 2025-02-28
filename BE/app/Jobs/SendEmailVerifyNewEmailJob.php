<?php

namespace App\Jobs;

use App\Mail\SendVerifyNewEmailMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SendEmailVerifyNewEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $newMail;
    public $otp;

    public function __construct($user, $newMail, $otp)
    {
        $this->user = $user;
        $this->newMail = $newMail;
        $this->otp = $otp;
    }

    public function handle(): void
    {
        try {
            Mail::to($this->newMail)->send(new SendVerifyNewEmailMail($this->user, $this->otp));
            Log::info("OTP đã được gửi đến: " . $this->newMail . " - OTP: " . $this->otp);
        } catch (\Exception $e) {
            Log::error("Lỗi khi gửi email xác thực: " . $e->getMessage());
        }
    }
}
