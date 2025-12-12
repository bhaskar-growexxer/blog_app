<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public User $user;
    public ?string $ip;

    public function __construct(User $user, ?string $ip = null)
    {
        $this->user = $user;
        $this->ip = $ip ?? request()->ip();
    }
}