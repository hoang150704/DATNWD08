<?php

namespace App\Jobs;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;

class SendResetPasswordEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;

    /**
     * Create a new job instance.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Tạo token reset mật khẩu
        $resetToken = Str::random(64);
        $user = User::where('email',$this->email)->first();
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->email],
            ['token' => $resetToken, 'created_at' => Carbon::now()]
        );
        
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $resetUrl = $frontendUrl . "/auth/reset-password?token=" . $resetToken;

        // Gửi email đặt lại mật khẩu
        Mail::to($this->email)->send(new ResetPasswordMail($user, $resetUrl));
    }
}
