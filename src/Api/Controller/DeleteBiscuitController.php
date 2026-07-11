<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\Biscuit;
use TeacherLi07\Anonymous\BiscuitGenerator;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteBiscuitController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $biscuit = Biscuit::whereNull('deleted_at')->findOrFail($id);
        $actor->assertCan('delete', $biscuit);

        $wasDefault = $biscuit->is_default;
        $biscuit->delete();

        if ($wasDefault) {
            $alternative = Biscuit::where('user_id', $actor->id)
                ->whereNull('deleted_at')
                ->where('is_frozen', false)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($alternative) {
                $alternative->is_default = true;
                $alternative->save();
            } else {
                $generator = resolve(BiscuitGenerator::class);
                $newBiscuit = new Biscuit();
                $newBiscuit->user_id = $actor->id;
                $newBiscuit->biscuit_string = $generator->generateUnique();
                $newBiscuit->is_default = true;
                $newBiscuit->is_frozen = false;
                $newBiscuit->save();
            }
        }
    }
}
