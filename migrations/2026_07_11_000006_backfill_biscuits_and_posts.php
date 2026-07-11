<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        // 1. Create default biscuit for each user who doesn't have one
        $usersWithoutBiscuit = $db->table('users')
            ->leftJoin('biscuits', function ($join) {
                $join->on('users.id', '=', 'biscuits.user_id')
                    ->whereNull('biscuits.deleted_at');
            })
            ->whereNull('biscuits.id')
            ->select('users.id')
            ->get();

        foreach ($usersWithoutBiscuit as $user) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            $string = '';
            for ($i = 0; $i < 7; $i++) {
                $string .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $db->table('biscuits')->insert([
                'user_id' => $user->id,
                'biscuit_string' => $string,
                'is_default' => true,
                'is_frozen' => false,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ]);
        }

        // 2. Backfill posts: set biscuit_string to author's default biscuit
        $postsWithoutBiscuit = $db->table('posts')
            ->whereNull('biscuit_string')
            ->select('posts.id', 'posts.user_id')
            ->get();

        foreach ($postsWithoutBiscuit as $post) {
            $defaultBiscuit = $db->table('biscuits')
                ->where('user_id', $post->user_id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first();

            if ($defaultBiscuit) {
                $db->table('posts')
                    ->where('id', $post->id)
                    ->update(['biscuit_string' => $defaultBiscuit->biscuit_string]);
            }
        }
    },

    'down' => function (Builder $schema) {
        // Backfill is not reversible: deleting biscuits would break referential integrity
    },
];
