<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::addColumns('users', [
    'is_anonymous_account' => ['boolean', 'default' => false],
]);
