<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;

class ListAccountBiscuitsController extends AbstractListController
{
    public $serializer = AccountBiscuitSerializer::class;

    public $include = ['biscuitUser'];

    protected function data(ServerRequestInterface $request, $document)
    {
        $actor = RequestUtil::getActor($request);

        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId && ! $actor->isAdmin()) {
            return [];
        }

        $query = AccountBiscuit::withTrashed();

        if ($actor->isAdmin() && $request->getQueryParams()['account_user_id'] ?? null) {
            $query->where('account_user_id', $request->getQueryParams()['account_user_id']);
        } else {
            $query->where('account_user_id', $accountUserId);
        }

        return $query->get();
    }
}
