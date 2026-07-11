<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Biscuit extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'biscuits';

    protected $casts = [
        'is_default' => 'bool',
        'is_frozen'  => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(\Flarum\User\User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_frozen', false);
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeUsable($query)
    {
        return $query->notDeleted()->active();
    }
}
