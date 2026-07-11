<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\Api\Serializer\BiscuitSerializer;
use TeacherLi07\Anonymous\Biscuit;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListBiscuitsController extends AbstractListController
{
    public $serializer = BiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $query = Biscuit::withTrashed()->where('user_id', $actor->id);

        $userId = $request->getQueryParams()['filter']['userId'] ?? null;
        if ($userId && $actor->isAdmin()) {
            $query = Biscuit::withTrashed()->where('user_id', $userId);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
