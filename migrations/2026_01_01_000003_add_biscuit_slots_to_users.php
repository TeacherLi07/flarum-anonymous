<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('users', [
    'biscuit_slots' => ['integer', 'unsigned' => true, 'default' => 1],
]);
