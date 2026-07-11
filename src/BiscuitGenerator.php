<?php

namespace TeacherLi07\Anonymous;

class BiscuitGenerator
{
    const CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const LENGTH = 7;

    public function generate(): string
    {
        $charsetLength = strlen(self::CHARSET);
        $result = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $result .= self::CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $result;
    }
}
