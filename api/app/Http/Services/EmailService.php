<?php

namespace App\Services;

use App\Mail\NewBlogNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\Blog;

class EmailService
{
    /**
     * Send a notification email for a new blog post.
     */
    public function sendNewBlogNotification(Blog $blog)
    {
        $recipient = $blog->author_email;
        // Mail::to($recipient)->send(new NewBlogNotification($blog));
    }
}
