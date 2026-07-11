<?php

namespace TeacherLi07\Anonymous;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountBiscuit extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'account_biscuits';

    protected $dates = ['deleted_at'];

    protected $casts = [
        'is_active' => 'bool',
        'is_frozen' => 'bool',
    ];

    public function accountUser()
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function biscuitUser()
    {
        return $this->belongsTo(User::class, 'biscuit_user_id');
    }

    public function scopeUsable($query)
    {
        return $query->where('is_frozen', false)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
