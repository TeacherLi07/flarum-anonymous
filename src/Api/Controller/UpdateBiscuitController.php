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
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateBiscuitController extends AbstractShowController
{
    public $serializer = BiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $body = $request->getParsedBody();
        $attributes = Arr::get($body, 'data.attributes', []);

        $biscuit = Biscuit::withTrashed()->findOrFail($id);
        $actor->assertCan('edit', $biscuit);

        if (array_key_exists('note', $attributes)) {
            $biscuit->note = $attributes['note'];
        }

        if (array_key_exists('isDefault', $attributes) && $attributes['isDefault']) {
            Biscuit::where('user_id', $actor->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            $biscuit->is_default = true;
        }

        if (array_key_exists('isFrozen', $attributes)) {
            $biscuit->is_frozen = (bool) $attributes['isFrozen'];
        }

        $biscuit->save();

        return $biscuit;
    }
}
