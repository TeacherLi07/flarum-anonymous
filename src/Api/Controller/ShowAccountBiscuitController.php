<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;

class ShowAccountBiscuitController extends AbstractShowController
{
    public $serializer = AccountBiscuitSerializer::class;

    public $include = ['biscuitUser'];

    protected function data(ServerRequestInterface $request, $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        $biscuit = AccountBiscuit::withTrashed()->findOrFail($id);

        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId || (int) $biscuit->account_user_id !== (int) $accountUserId) {
            if (! $actor->isAdmin()) {
                throw new \Flarum\User\Exception\PermissionDeniedException();
            }
        }

        return $biscuit;
    }
}
