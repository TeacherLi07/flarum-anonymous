<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class SlotManager
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function calculateAvailableSlots(User $user): int
    {
        $max = (int) $this->settings->get('anonymous.slot_max', 5);
        $daysRequired = (int) $this->settings->get('anonymous.slot_days_required', 7);
        $postsRequired = (int) $this->settings->get('anonymous.slot_posts_required', 30);

        $days = max(0, $user->created_at->diffInDays(now()));
        $posts = max(0, (int) ($user->comment_count ?? 0) + (int) ($user->discussion_count ?? 0));

        $slotsByDays = $daysRequired > 0 ? intdiv($days, $daysRequired) : 9999;
        $slotsByPosts = $postsRequired > 0 ? intdiv($posts, $postsRequired) : 9999;

        $slots = 1 + min($slotsByDays, $slotsByPosts);

        return max(1, min($slots, $max));
    }

    public function needsFreeze(User $user): bool
    {
        $allowed = $this->calculateAvailableSlots($user);
        $usable = Biscuit::where('user_id', $user->id)->usable()->count();

        return $usable > $allowed;
    }

    public function freezeCount(User $user): int
    {
        $allowed = $this->calculateAvailableSlots($user);
        $usable = Biscuit::where('user_id', $user->id)->usable()->count();

        return max(0, $usable - $allowed);
    }
}
