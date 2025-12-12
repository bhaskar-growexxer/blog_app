<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $ip;

    public function __construct(User $user, ?string $ip = null)
    {
        $this->user = $user;
        $this->ip = $ip;
    }

    public function build()
    {
        return $this->subject('New login to your account')
                    ->view('emails.login_notification')
                    ->with([
                        'name' => $this->user->name,
                        'ip' => $this->ip,
                    ]);
    }
}