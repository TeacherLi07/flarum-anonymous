<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Huihu\Anonymous\Listener;

use Flarum\User\Event\Registered;
use Huihu\Anonymous\Biscuit;
use Huihu\Anonymous\BiscuitGenerator;

class CreateInitialBiscuit
{
    public function handle(Registered $event)
    {
        $user = $event->user;
        $generator = resolve(BiscuitGenerator::class);

        $biscuit = new Biscuit();
        $biscuit->user_id = $user->id;
        $biscuit->biscuit_string = $generator->generateUnique();
        $biscuit->is_default = true;
        $biscuit->is_frozen = false;
        $biscuit->save();
    }
}
