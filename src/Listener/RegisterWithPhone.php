<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Huihu\Anonymous\Listener;

use Flarum\User\Event\Registering;
use Huihu\Anonymous\Auth\SmsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RegisterWithPhone
{
    public function handle(Registering $event)
    {
        $data = $event->data;
        $phone = Arr::get($data, 'attributes.phone');
        $code = Arr::get($data, 'attributes.verificationCode');

        if ($phone && $code) {
            $smsService = resolve(SmsService::class);

            if (!$smsService->verify($phone, $code)) {
                throw new \RuntimeException('Invalid verification code.');
            }

            $event->data['attributes']['phone'] = $phone;
            $event->data['attributes']['phone_verified_at'] = now()->toDateTimeString();
        }

        // Auto-generate UUID as username if not provided
        if (empty($event->data['attributes']['username'])) {
            $event->data['attributes']['username'] = (string) Str::uuid();
        }
    }
}
