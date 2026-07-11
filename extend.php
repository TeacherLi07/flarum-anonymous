<?php

use Flarum\Extend;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\User\Event\LoggedIn;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving;
use TeacherLi07\Anonymous\Api\Controller;
use TeacherLi07\Anonymous\Listener;
use TeacherLi07\Anonymous\Middleware;
use TeacherLi07\Anonymous\SlotManager;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/biscuits', 'biscuits.manager'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    // Migrations are auto-discovered from the migrations/ directory
    // by Flarum when the extension is enabled.

    // Settings
    (new Extend\Settings())
        ->default('anonymous.slot_days_required', 7)
        ->default('anonymous.slot_posts_required', 30)
        ->default('anonymous.slot_max', 5)
        ->default('anonymous.sms_access_key_id', '')
        ->default('anonymous.sms_access_secret', '')
        ->default('anonymous.sms_sign_name', '')
        ->default('anonymous.sms_template_code', '')
        ->serializeToForum('anonymousSlotMax', 'anonymous.slot_max')
        ->serializeToForum('anonymousSlotDaysRequired', 'anonymous.slot_days_required')
        ->serializeToForum('anonymousSlotPostsRequired', 'anonymous.slot_posts_required'),

    // API Routes
    (new Extend\Routes('api'))
        ->post('/anonymous/register', 'anonymous.register', Controller\RegisterWithPhoneController::class)
        ->post('/sms/send', 'sms.send', Controller\SendSmsCodeController::class)
        ->get('/account/biscuits', 'account.biscuits.index', Controller\ListAccountBiscuitsController::class)
        ->post('/account/biscuits', 'account.biscuits.create', Controller\CreateAccountBiscuitController::class)
        ->get('/account/biscuits/{id}', 'account.biscuits.show', Controller\ShowAccountBiscuitController::class)
        ->patch('/account/biscuits/{id}', 'account.biscuits.update', Controller\UpdateAccountBiscuitController::class)
        ->delete('/account/biscuits/{id}', 'account.biscuits.delete', Controller\DeleteAccountBiscuitController::class)
        ->patch('/account/biscuits/batch/freeze', 'account.biscuits.freeze', Controller\BatchFreezeAccountBiscuitsController::class)
        ->post('/session/acting', 'session.acting', Controller\SwitchActingBiscuitController::class)
        ->post('/account/bind-phone', 'account.bind-phone', Controller\BindPhoneController::class),

    // Forum Routes - /biscuits is handled by frontend SPA router

    // Event Listeners
    (new Extend\Event())
        ->listen(Saving::class, Listener\RegisterWithPhone::class)
        ->listen(Registered::class, Listener\CreateInitialBiscuit::class)
        ->listen(LoggedIn::class, Listener\SetActingBiscuitOnLogin::class),

    // Middleware
    (new Extend\Middleware('api'))
        ->insertAfter(
            \Flarum\Http\Middleware\AuthenticateWithSession::class,
            Middleware\ActorSwapMiddleware::class
        ),

    (new Extend\Middleware('forum'))
        ->insertAfter(
            \Flarum\Http\Middleware\AuthenticateWithSession::class,
            Middleware\ActorSwapMiddleware::class
        ),

    (new Extend\Middleware('api'))
        ->insertBefore(
            \Flarum\Http\Middleware\AuthenticateWithHeader::class,
            Middleware\RestrictLoginIdentification::class
        ),

    // CSRF exclusion for public endpoints
    (new Extend\Csrf())
        ->exemptRoute('sms.send')
        ->exemptRoute('anonymous.register'),

    // Forum Serializer extensions
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('needBiscuitFreeze', function ($serializer) {
            $session = $serializer->getRequest()->getAttribute('session');
            $accountUserId = $session ? $session->get('account_id') : null;

            if (! $accountUserId) {
                return false;
            }

            $accountUser = \Flarum\User\User::find($accountUserId);

            if (! $accountUser) {
                return false;
            }

            return resolve(SlotManager::class)->needsFreeze($accountUser);
        })
        ->attribute('needPhoneBinding', function ($serializer) {
            $session = $serializer->getRequest()->getAttribute('session');
            $accountUserId = $session ? $session->get('account_id') : null;

            if (! $accountUserId) {
                return false;
            }

            $accountUser = \Flarum\User\User::find($accountUserId);

            return $accountUser && empty($accountUser->phone);
        })
        ->attribute('canManageBiscuits', function ($serializer) {
            $session = $serializer->getRequest()->getAttribute('session');
            return $session && $session->get('account_id') !== null;
        })
        ->attribute('activeBiscuitUserId', function ($serializer) {
            $session = $serializer->getRequest()->getAttribute('session');
            return $session ? $session->get('active_biscuit_user_id') : null;
        })
        ->attribute('accountUserId', function ($serializer) {
            $session = $serializer->getRequest()->getAttribute('session');
            return $session ? $session->get('account_id') : null;
        }),

    // User Serializer - expose display name for biscuit users
    (new Extend\ApiSerializer(\Flarum\Api\Serializer\UserSerializer::class))
        ->attribute('isAnonymousAccount', function ($serializer, $user) {
            return (bool) ($user->is_anonymous_account ?? false);
        })
        ->attribute('phone', function ($serializer, $user) {
            $actor = $serializer->getActor();
            $session = $serializer->getRequest()->getAttribute('session');
            $accountId = $session ? $session->get('account_id') : null;

            if ($actor->isAdmin() || $accountId == $user->id) {
                return $user->phone;
            }

            return null;
        })
        ->attribute('canManageBiscuits', function ($serializer, $user) {
            $session = $serializer->getRequest()->getAttribute('session');
            $accountId = $session ? $session->get('account_id') : null;
            return $accountId == $user->id;
        })
        ->attribute('biscuitSlots', function ($serializer, $user) {
            $session = $serializer->getRequest()->getAttribute('session');
            $accountId = $session ? $session->get('account_id') : null;

            if ($accountId != $user->id) {
                return null;
            }

            return resolve(SlotManager::class)->calculateAvailableSlots($user);
        }),
];
