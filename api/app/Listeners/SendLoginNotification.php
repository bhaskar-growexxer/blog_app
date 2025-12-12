<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Mail\LoginNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendLoginNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;
        Mail::to($user->email)->send(new LoginNotificationMail($user, $event->ip));
    }
}