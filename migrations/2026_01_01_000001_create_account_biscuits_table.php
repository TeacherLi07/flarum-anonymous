<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('account_biscuits', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('account_user_id')->index();
    $table->unsignedInteger('biscuit_user_id')->index();
    $table->string('biscuit_string', 7);
    $table->text('note')->nullable();
    $table->boolean('is_active')->default(0);
    $table->boolean('is_frozen')->default(0);
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
    $table->softDeletes();

    $table->foreign('account_user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('biscuit_user_id')->references('id')->on('users')->onDelete('cascade');

    // raw expression for generated column (MySQL only)
    $table->unique('biscuit_string');
});
