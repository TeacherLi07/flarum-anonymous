<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListDiscussionsByBiscuitController extends AbstractListController
{
    public $serializer = DiscussionSerializer::class;

    public $include = ['user', 'lastPostedUser', 'firstPost', 'lastPostedPost', 'tags'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $biscuitString = Arr::get($request->getQueryParams(), 'filter.biscuit');

        if (!$biscuitString) {
            return Discussion::query()->limit(0)->get();
        }

        $discussionIds = \Flarum\Post\Post::where('biscuit_string', $biscuitString)
            ->select('discussion_id')
            ->distinct()
            ->pluck('discussion_id');

        return Discussion::whereIn('id', $discussionIds)
            ->whereVisibleTo(\Flarum\Http\RequestUtil::getActor($request))
            ->orderBy('last_posted_at', 'desc')
            ->get();
    }
}
