<?php

namespace TeacherLi07\Anonymous\Middleware;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TeacherLi07\Anonymous\AccountBiscuit;

class ActorSwapMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isGuest() && ! empty($actor->is_anonymous_account)) {
            $activeBiscuitUserId = session('active_biscuit_user_id');

            if (! $activeBiscuitUserId) {
                $defaultBiscuit = AccountBiscuit::where('account_user_id', $actor->id)
                    ->active()
                    ->usable()
                    ->first();

                if ($defaultBiscuit) {
                    $activeBiscuitUserId = $defaultBiscuit->biscuit_user_id;
                    session()->put('active_biscuit_user_id', $activeBiscuitUserId);
                }
            }

            if ($activeBiscuitUserId) {
                $biscuitUser = User::find($activeBiscuitUserId);

                if ($biscuitUser) {
                    $request = RequestUtil::withActor($request, $biscuitUser);
                }
            }
        }

        return $handler->handle($request);
    }
}
