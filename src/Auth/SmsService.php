<?php

namespace TeacherLi07\Anonymous\Auth;

use Illuminate\Contracts\Cache\Repository as Cache;

class SmsService
{
    protected $cache;

    const CODE_TTL = 300;
    const COOLDOWN_TTL = 60;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function send(string $phone): void
    {
        if ($this->cache->has("sms:cooldown:{$phone}")) {
            return;
        }

        $code = '123456';

        // In production, this would call Alibaba Cloud SMS API
        // For dev/testing, store a fixed code

        $this->cache->put("sms:code:{$phone}", $code, self::CODE_TTL);
        $this->cache->put("sms:cooldown:{$phone}", true, self::COOLDOWN_TTL);
    }

    public function verify(string $phone, string $code): bool
    {
        $stored = $this->cache->get("sms:code:{$phone}");

        if ($stored && $stored === $code) {
            $this->cache->forget("sms:code:{$phone}");

            return true;
        }

        return false;
    }
}
