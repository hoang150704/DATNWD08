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
use App\Mail\VerifyEmailUserMail;

class SendEmailVerificationUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        // Tạo token xác thực email
        $verificationToken = Str::random(64);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $this->user->email],
            ['token' => $verificationToken, 'created_at' => Carbon::now()]
        );

        // Tạo link xác thực
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $verificationUrl = $frontendUrl . "/auth/verify?token=" . $verificationToken;

        // Gửi email xác thực với template đẹp
        Mail::to($this->user->email)->send(new VerifyEmailUserMail($this->user, $verificationUrl));
    }
}
