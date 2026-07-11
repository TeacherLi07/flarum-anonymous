<?php

namespace TeacherLi07\Anonymous\Listener;

use Flarum\User\Event\LoggedIn;
use Illuminate\Contracts\Session\Session;
use TeacherLi07\Anonymous\AccountBiscuit;

class SetActingBiscuitOnLogin
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function handle(LoggedIn $event): void
    {
        $user = $event->user;

        if (empty($user->is_anonymous_account)) {
            return;
        }

        $this->session->put('account_id', $user->id);

        $activeBiscuit = AccountBiscuit::where('account_user_id', $user->id)
            ->active()
            ->usable()
            ->first();

        if ($activeBiscuit) {
            $this->session->put('active_biscuit_user_id', $activeBiscuit->biscuit_user_id);
        }
    }
}
