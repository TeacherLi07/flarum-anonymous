<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller as FlarumController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving as UserSaving;
use Flarum\User\User;
use TeacherLi07\Anonymous\Access\BiscuitPolicy;
use TeacherLi07\Anonymous\Api\Controller;
use TeacherLi07\Anonymous\Api\Serializer\BiscuitSerializer;
use TeacherLi07\Anonymous\Biscuit;
use TeacherLi07\Anonymous\Listener;
use TeacherLi07\Anonymous\Middleware\InjectBiscuitToPostData;
use TeacherLi07\Anonymous\SlotManager;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/biscuits', 'teacherli07-anonymous.biscuits')
        ->route('/b/{biscuitString}', 'teacherli07-anonymous.biscuitProfile'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('api'))
        ->get('/biscuits', 'biscuits.index', Controller\ListBiscuitsController::class)
        ->get('/biscuits/{id}', 'biscuits.show', Controller\ShowBiscuitController::class)
        ->post('/biscuits', 'biscuits.create', Controller\CreateBiscuitController::class)
        ->patch('/biscuits/{id}', 'biscuits.update', Controller\UpdateBiscuitController::class)
        ->delete('/biscuits/{id}', 'biscuits.delete', Controller\DeleteBiscuitController::class),
        
    (new Extend\Routes('api'))
        ->patch('/biscuits/batch/freeze', 'biscuits.freeze', Controller\BatchFreezeBiscuitsController::class),

    (new Extend\Routes('api'))
        ->post('/sms/send', 'sms.send', Controller\SendSmsCodeController::class),

    (new Extend\Model(User::class))
        ->cast('phone_verified_at', 'datetime')
        ->hasMany('biscuits', Biscuit::class, 'user_id'),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('needBiscuitFreeze', function (ForumSerializer $serializer) {
            $actor = $serializer->getActor();
            if (!$actor || $actor->isGuest()) return false;
            return resolve(SlotManager::class)->needsFreeze($actor);
        })
        ->attribute('canManageBiscuits', function (ForumSerializer $serializer) {
            $actor = $serializer->getActor();
            return $actor && !$actor->isGuest();
        }),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('displayName', function (UserSerializer $serializer, $user) {
            $default = Biscuit::where('user_id', $user->id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first();
            return $default ? $default->biscuit_string : null;
        })
        ->attribute('biscuitSlots', function (UserSerializer $serializer, $user) {
            return (int) ($user->biscuit_slots ?? 1);
        }),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->attribute('biscuitString', function (PostSerializer $serializer, $post) {
            return $post->biscuit_string;
        })
        ->attribute('biscuitIsDeleted', function (PostSerializer $serializer, $post) {
            if (!$post->biscuit_string) return null;
            $biscuit = Biscuit::withTrashed()
                ->where('biscuit_string_lower', strtolower($post->biscuit_string))
                ->where('user_id', $post->user_id)
                ->first();
            return $biscuit ? $biscuit->trashed() : false;
        })
        ->attribute('biscuitIsFrozen', function (PostSerializer $serializer, $post) {
            if (!$post->biscuit_string) return null;
            $biscuit = Biscuit::withTrashed()
                ->where('biscuit_string_lower', strtolower($post->biscuit_string))
                ->where('user_id', $post->user_id)
                ->first();
            return $biscuit ? (bool) $biscuit->is_frozen : false;
        }),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('biscuitString', function (DiscussionSerializer $serializer, $discussion) {
            return optional($discussion->firstPost)->biscuit_string;
        }),

    /* Temporarily disabled for debugging
    (new Extend\ApiController(FlarumController\ShowDiscussionController::class))
        ->prepareDataForSerialization(function ($controller, $discussion, $request, $document) {
            $actor = \Flarum\Http\RequestUtil::getActor($request);
            if ($actor && !$actor->isGuest()) {
                $lastPost = \Flarum\Post\Post::where('discussion_id', $discussion->id)
                    ->where('user_id', $actor->id)
                    ->whereNotNull('biscuit_string')
                    ->orderBy('created_at', 'desc')
                    ->first();
                $document->setMeta('lastUsedBiscuitString', $lastPost ? $lastPost->biscuit_string : null);
            }
        }),
    */

    (new Extend\Settings())
        ->serializeToForum('slotDaysRequired', 'anonymous.slot_days_required', null, '7')
        ->serializeToForum('slotPostsRequired', 'anonymous.slot_posts_required', null, '30')
        ->serializeToForum('slotMax', 'anonymous.slot_max', null, '5'),

    (new Extend\Middleware('api'))
        ->add(InjectBiscuitToPostData::class),

    (new Extend\Policy())
        ->modelPolicy(Biscuit::class, BiscuitPolicy::class),

    (new Extend\Event())
        ->listen(UserSaving::class, Listener\RegisterWithPhone::class)
        ->listen(Registered::class, Listener\CreateInitialBiscuit::class)
        ->listen(PostSaving::class, Listener\AddBiscuitToPost::class),

    new Extend\Locales(__DIR__.'/locale'),
];
