<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;

class SwitchActingBiscuitController extends AbstractShowController
{
    public $serializer = \Flarum\Api\Serializer\CurrentUserSerializer::class;

    protected function data(ServerRequestInterface $request, $document)
    {
        $actor = RequestUtil::getActor($request);
        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId) {
            throw new \Flarum\User\Exception\PermissionDeniedException();
        }

        $body = $request->getParsedBody();
        $biscuitId = Arr::get($body, 'data.attributes.biscuitId');

        if ($biscuitId) {
            $biscuit = AccountBiscuit::where('account_user_id', $accountUserId)
                ->where('id', $biscuitId)
                ->usable()
                ->firstOrFail();

            AccountBiscuit::where('account_user_id', $accountUserId)
                ->update(['is_active' => false]);

            $biscuit->is_active = true;
            $biscuit->save();

            $request->getAttribute('session')->put('active_biscuit_user_id', $biscuit->biscuit_user_id);
        } else {
            AccountBiscuit::where('account_user_id', $accountUserId)
                ->update(['is_active' => false]);

            $request->getAttribute('session')->forget('active_biscuit_user_id');
        }

        return $actor;
    }
}
