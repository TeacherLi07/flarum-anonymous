<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Listener;

use Flarum\Post\Event\Saving;
use TeacherLi07\Anonymous\Biscuit;
use TeacherLi07\Anonymous\SlotManager;

class AddBiscuitToPost
{
    public function handle(Saving $event)
    {
        $post = $event->post;

        if ($post->exists) {
            return;
        }

        $actor = $event->actor;
        if (!$actor || $actor->isGuest()) {
            return;
        }

        $biscuitString = $event->data['biscuit_string'] ?? null;

        if (!$biscuitString && isset($event->data['attributes']['biscuit_string'])) {
            $biscuitString = $event->data['attributes']['biscuit_string'];
        }

        if (!$biscuitString) {
            $discussionId = $post->discussion_id;

            if ($discussionId) {
                $lastPost = \Flarum\Post\Post::where('discussion_id', $discussionId)
                    ->where('user_id', $actor->id)
                    ->whereNotNull('biscuit_string')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastPost) {
                    $biscuitString = $lastPost->biscuit_string;
                }
            }

            if (!$biscuitString) {
                $default = Biscuit::where('user_id', $actor->id)
                    ->where('is_default', true)
                    ->whereNull('deleted_at')
                    ->first();

                if ($default) {
                    $biscuitString = $default->biscuit_string;
                }
            }
        }

        if (!$biscuitString) {
            throw new \RuntimeException('No biscuit available. Please manage your biscuits first.');
        }

        $biscuit = Biscuit::withTrashed()
            ->where('biscuit_string_lower', strtolower($biscuitString))
            ->where('user_id', $actor->id)
            ->first();

        if (!$biscuit) {
            throw new \RuntimeException('Invalid biscuit.');
        }

        if ($biscuit->trashed()) {
            throw new \RuntimeException('This biscuit has been deleted.');
        }

        if ($biscuit->is_frozen) {
            throw new \RuntimeException('This biscuit is frozen and cannot be used to post.');
        }

        $slotManager = resolve(SlotManager::class);
        if ($slotManager->needsFreeze($actor)) {
            throw new \RuntimeException('You have too many active biscuits. Please freeze some before posting.');
        }

        $post->biscuit_string = $biscuitString;
    }
}
