<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('biscuits', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned()->nullable();
    $table->string('biscuit_string', 7)->collation('utf8mb4_bin');
    $table->string('biscuit_string_lower', 7)->storedAs('LOWER(biscuit_string)');
    $table->text('note')->nullable();
    $table->boolean('is_default')->default(false);
    $table->boolean('is_frozen')->default(false);
    $table->timestamps();
    $table->softDeletes();

    $table->unique('biscuit_string_lower');
    $table->index('user_id');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
});
