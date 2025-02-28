<?php 
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    public function __construct($user, $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->subject('Xác thực tài khoản của bạn')
                    ->markdown('emails.verify-email')
                    ->with([
                        'name' => $this->user->name,
                        'verificationUrl' => $this->verificationUrl
                    ]);
    }
}
