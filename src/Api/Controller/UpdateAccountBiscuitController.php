<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;

class UpdateAccountBiscuitController extends AbstractShowController
{
    public $serializer = AccountBiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, $document)
    {
        $id = $request->getAttribute('id');
        $actor = RequestUtil::getActor($request);
        $body = $request->getParsedBody();
        $attributes = Arr::get($body, 'data.attributes', []);

        $biscuit = AccountBiscuit::withTrashed()->findOrFail($id);
        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId || (int) $biscuit->account_user_id !== (int) $accountUserId) {
            if (! $actor->isAdmin()) {
                throw new \Flarum\User\Exception\PermissionDeniedException();
            }
        }

        if (array_key_exists('note', $attributes)) {
            $biscuit->note = $attributes['note'];
        }

        if (array_key_exists('isActive', $attributes)) {
            $isActive = (bool) $attributes['isActive'];

            if ($isActive) {
                AccountBiscuit::where('account_user_id', $biscuit->account_user_id)
                    ->where('id', '!=', $biscuit->id)
                    ->update(['is_active' => false]);

                $biscuit->is_active = true;
                $request->getAttribute('session')->put('active_biscuit_user_id', $biscuit->biscuit_user_id);
            }
        }

        if (array_key_exists('isFrozen', $attributes)) {
            $biscuit->is_frozen = (bool) $attributes['isFrozen'];
        }

        $biscuit->save();

        return $biscuit;
    }
}
