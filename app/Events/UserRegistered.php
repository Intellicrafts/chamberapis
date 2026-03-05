<?php

namespace App\Events;

use App\Models\User;
use App\Models\Lawyer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Lawyer $lawyer = null,   // Non-null if this is a lawyer account
    ) {}
}
