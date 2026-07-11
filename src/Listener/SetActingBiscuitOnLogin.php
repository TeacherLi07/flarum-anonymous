<?php

namespace TeacherLi07\Anonymous\Listener;

use Flarum\User\Event\LoggedIn;
use TeacherLi07\Anonymous\AccountBiscuit;

class SetActingBiscuitOnLogin
{
    public function handle(LoggedIn $event): void
    {
        $user = $event->user;

        if (empty($user->is_anonymous_account)) {
            return;
        }

        $session = resolve('session');
        $session->put('account_id', $user->id);

        $activeBiscuit = AccountBiscuit::where('account_user_id', $user->id)
            ->active()
            ->usable()
            ->first();

        if ($activeBiscuit) {
            $session->put('active_biscuit_user_id', $activeBiscuit->biscuit_user_id);
        }
    }
}
