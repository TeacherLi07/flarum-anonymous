<?php

namespace TeacherLi07\Anonymous\Listener;

use Flarum\User\Event\Saving;
use Flarum\User\User;
use Illuminate\Support\Str;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Auth\SmsService;
use TeacherLi07\Anonymous\BiscuitGenerator;

class RegisterWithPhone
{
    protected $sms;
    protected $generator;

    public function __construct(SmsService $sms, BiscuitGenerator $generator)
    {
        $this->sms = $sms;
        $this->generator = $generator;
    }

    public function handle(Saving $event): void
    {
        if ($event->user->exists) {
            return;
        }

        $data = $event->data;
        $phone = $data['attributes']['phone'] ?? null;
        $code = $data['attributes']['verificationCode'] ?? null;
        $password = $data['attributes']['password'] ?? null;

        if (! $phone || ! $code || ! $password) {
            return;
        }

        if (! $this->sms->verify($phone, $code)) {
            return;
        }

        $event->user->phone = $phone;
        $event->user->phone_verified_at = now();
        $event->user->is_anonymous_account = true;
        $event->user->username = substr(sha1($phone), 0, 30);
        $event->user->email = $phone . '@anonymous.local';
    }
}
