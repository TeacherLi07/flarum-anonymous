<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\Api\Serializer\BiscuitSerializer;
use TeacherLi07\Anonymous\Biscuit;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowBiscuitController extends AbstractShowController
{
    public $serializer = BiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $biscuit = Biscuit::withTrashed()->findOrFail($id);

        $actor->assertCan('show', $biscuit);

        return $biscuit;
    }
}
