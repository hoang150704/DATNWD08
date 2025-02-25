<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class SendEmailVerificationUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Tạo token xác thực email
        $verificationToken = Str::random(64);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $this->user->email],
            ['token' => $verificationToken, 'created_at' => Carbon::now()]
        );

        // Tạo link xác thực
        $verificationUrl = url("/api/verify-email?token=$verificationToken");

        // Gửi email xác thực
        Mail::raw("Click vào đây để xác thực email của bạn: $verificationUrl", function ($message) {
            $message->to($this->user->email)
                    ->subject('Xác thực email');
        });
    }
}
