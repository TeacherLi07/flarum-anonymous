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
use Carbon\Carbon;

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
            $user->phone_verified_at = Carbon::now();
        }

        // Auto-generate UUID-based username if not provided (max 30 chars)
        if (empty($user->username)) {
            $user->username = str_replace('-', '', (string) Str::uuid());
            $user->username = substr($user->username, 0, 30);
        }

        // Skip email confirmation (SMTP not configured)
        $user->activate();
    }
}
