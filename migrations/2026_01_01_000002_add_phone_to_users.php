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
        $schema->table('users', function ($table) {
            $table->string('phone', 20)->nullable()->unique();
        });
        $schema->table('users', function ($table) {
            $table->dateTime('phone_verified_at')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('users', function ($table) {
            $table->dropColumn('phone_verified_at');
        });
        $schema->table('users', function ($table) {
            $table->dropColumn('phone');
        });
    },
];
