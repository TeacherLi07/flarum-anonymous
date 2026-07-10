<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Huihu\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Huihu\Anonymous\Api\Serializer\BiscuitSerializer;
use Huihu\Anonymous\Biscuit;
use Huihu\Anonymous\SlotManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class BatchFreezeBiscuitsController extends AbstractShowController
{
    public $serializer = BiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $body = $request->getParsedBody();
        $ids = Arr::get($body, 'data.ids', []);
        $freezeCount = Arr::get($body, 'data.freezeCount', 0);

        $biscuits = Biscuit::where('user_id', $actor->id)
            ->usable()
            ->whereIn('id', $ids)
            ->get();

        if ($biscuits->count() < $freezeCount) {
            throw new \RuntimeException('Not enough biscuits selected for freezing.');
        }

        $biscuits->take($freezeCount)->each(function ($biscuit) {
            $biscuit->is_frozen = true;
            $biscuit->save();
        });

        return $biscuits;
    }
}
