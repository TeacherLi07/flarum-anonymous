<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;

class BatchFreezeAccountBiscuitsController extends AbstractShowController
{
    public $serializer = AccountBiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, $document)
    {
        $actor = RequestUtil::getActor($request);
        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId) {
            throw new \Flarum\User\Exception\PermissionDeniedException();
        }

        $body = $request->getParsedBody();
        $ids = Arr::get($body, 'data.attributes.ids', []);

        if (empty($ids)) {
            throw new \RuntimeException('No biscuit IDs provided.');
        }

        $biscuits = AccountBiscuit::where('account_user_id', $accountUserId)
            ->whereIn('id', $ids)
            ->get();

        $afterFreezeCount = AccountBiscuit::where('account_user_id', $accountUserId)
            ->usable()
            ->whereNotIn('id', $ids)
            ->count();

        if ($afterFreezeCount < 1) {
            throw new \RuntimeException('At least one active biscuit required.');
        }

        foreach ($biscuits as $biscuit) {
            $biscuit->is_frozen = true;
            $biscuit->is_active = false;
            $biscuit->save();
        }

        return $biscuits->first();
    }
}
