<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::addColumns('users', [
    'phone' => ['string', 'length' => 20, 'nullable' => true, 'unique' => true],
    'phone_verified_at' => ['dateTime', 'nullable' => true],
]);
