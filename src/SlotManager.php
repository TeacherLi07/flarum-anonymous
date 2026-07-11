<?php

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
        $slotDays = (int) ($this->settings->get('anonymous.slot_days_required') ?: 7);
        $slotPosts = (int) ($this->settings->get('anonymous.slot_posts_required') ?: 30);
        $slotMax = (int) ($this->settings->get('anonymous.slot_max') ?: 5);

        $daysSinceRegistration = $user->joined_at
            ? max(0, now()->diffInDays($user->joined_at))
            : 0;

        $totalPosts = $user->comment_count + $user->discussion_count;

        $slotsByDays = (int) floor($daysSinceRegistration / $slotDays);
        $slotsByPosts = (int) floor($totalPosts / $slotPosts);

        $available = 1 + min($slotsByDays, $slotsByPosts);

        return min($available, $slotMax);
    }

    public function needsFreeze(User $accountUser): bool
    {
        $usableCount = AccountBiscuit::where('account_user_id', $accountUser->id)
            ->usable()
            ->count();

        $availableSlots = $this->calculateAvailableSlots($accountUser);

        return $usableCount > $availableSlots;
    }

    public function getSlotMax(): int
    {
        return (int) ($this->settings->get('anonymous.slot_max') ?: 5);
    }

    public function getSlotDaysRequired(): int
    {
        return (int) ($this->settings->get('anonymous.slot_days_required') ?: 7);
    }

    public function getSlotPostsRequired(): int
    {
        return (int) ($this->settings->get('anonymous.slot_posts_required') ?: 30);
    }
}
