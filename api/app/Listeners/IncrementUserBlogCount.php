<?php

namespace App\Listeners;

use App\Events\BlogCreated;
use App\Mail\BlogCreatedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class IncrementUserBlogCount implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BlogCreated $event): void
    {
        $blog = $event->blog;
        $user = User::where('email', $blog->author)->first();

        if (! $user) {
            return;
        }

        // increment total_blogs
        $user->increment('total_blogs');

        // send email notification
        Mail::to($user->email)->send(new BlogCreatedMail($blog, $user));
    }
}