<?php

namespace App\Mail;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BlogCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Blog $blog;
    public User $user;

    public function __construct(Blog $blog, User $user)
    {
        $this->blog = $blog;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Your blog was created')
                    ->view('emails.blog_created')
                    ->with([
                        'blogTitle' => $this->blog->title,
                        'userName' => $this->user->name,
                        'totalBlogs' => $this->user->total_blogs,
                    ]);
    }
}