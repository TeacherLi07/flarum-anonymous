<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use TeacherLi07\Anonymous\Biscuit;

class BiscuitPolicy extends AbstractPolicy
{
    public function claimBiscuit(User $actor)
    {
        if ($actor->isGuest()) {
            return $this->deny();
        }
    }

    public function edit(User $actor, Biscuit $biscuit)
    {
        if ($actor->isGuest()) {
            return $this->deny();
        }

        if ($actor->id === $biscuit->user_id) {
            return $this->allow();
        }
    }

    public function delete(User $actor, Biscuit $biscuit)
    {
        if ($actor->isGuest()) {
            return $this->deny();
        }

        if ($actor->suspended_until && $actor->suspended_until->isFuture()) {
            return $this->deny();
        }

        if ($actor->id === $biscuit->user_id) {
            return $this->allow();
        }
    }

    public function show(User $actor, Biscuit $biscuit)
    {
        if ($actor->isAdmin()) {
            return $this->allow();
        }

        if ($actor->id === $biscuit->user_id) {
            return $this->allow();
        }
    }
}
