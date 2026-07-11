<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous;

class BiscuitGenerator
{
    private const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH = 7;

    public function generate(): string
    {
        $charsLen = strlen(self::CHARS) - 1;

        $string = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $string .= self::CHARS[random_int(0, $charsLen)];
        }

        return $string;
    }

    public function generateUnique(): string
    {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $string = $this->generate();

            if (!Biscuit::withTrashed()->where('biscuit_string_lower', strtolower($string))->exists()) {
                return $string;
            }
        }

        throw new \RuntimeException('Failed to generate unique biscuit string after ' . $maxAttempts . ' attempts');
    }
}
