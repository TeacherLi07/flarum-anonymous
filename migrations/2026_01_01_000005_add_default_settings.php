<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addSettings([
    'anonymous.sms_provider'        => 'aliyun',
    'anonymous.sms_access_key_id'   => '',
    'anonymous.sms_access_secret'   => '',
    'anonymous.sms_sign_name'       => '',
    'anonymous.sms_template_code'   => '',
    'anonymous.slot_days_required'  => '7',
    'anonymous.slot_posts_required' => '30',
    'anonymous.slot_max'            => '5',
]);
