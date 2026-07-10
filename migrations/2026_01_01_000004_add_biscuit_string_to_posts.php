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
        $schema->table('posts', function ($table) {
            $table->string('biscuit_string', 7)->nullable();
        });
        $schema->table('posts', function ($table) {
            $table->index('biscuit_string');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('posts', function ($table) {
            $table->dropIndex(['biscuit_string']);
        });
        $schema->table('posts', function ($table) {
            $table->dropColumn('biscuit_string');
        });
    },
];
