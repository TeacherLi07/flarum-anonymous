<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Listener;

use Flarum\User\Event\Saving;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RegisterWithPhone
{
    public function handle(Saving $event)
    {
        $user = $event->user;
        $data = $event->data;

        if ($user->exists) {
            return;
        }

        // SMS mock: accept any phone + code combination
        $phone = Arr::get($data, 'attributes.phone');
        if ($phone) {
            $user->phone = $phone;
            $user->phone_verified_at = now();
        }

        // Auto-generate UUID as username if not provided
        if (empty($user->username)) {
            $user->username = (string) Str::uuid();
        }
    }
}
