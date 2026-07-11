<?php

namespace TeacherLi07\Anonymous\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Support\Carbon;

class AccountBiscuitPolicy extends AbstractPolicy
{
    public function claimBiscuit(User $actor): ?string
    {
        if ($actor->isGuest()) {
            return $this->deny();
        }

        if ($actor->suspended_until && $actor->suspended_until > Carbon::now()) {
            return $this->deny();
        }

        return null;
    }

    public function edit(User $actor, $biscuit): ?string
    {
        if ($actor->isGuest()) {
            return $this->deny();
        }

        $accountUserId = resolve('session')->get('account_id');

        if ($accountUserId && (int) $biscuit->account_user_id === (int) $accountUserId) {
            return null;
        }

        if ($actor->isAdmin()) {
            return null;
        }

        return $this->deny();
    }

    public function delete(User $actor, $biscuit): ?string
    {
        return $this->edit($actor, $biscuit);
    }
}
