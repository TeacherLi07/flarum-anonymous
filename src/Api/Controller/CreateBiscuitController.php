<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use TeacherLi07\Anonymous\Api\Serializer\BiscuitSerializer;
use TeacherLi07\Anonymous\Biscuit;
use TeacherLi07\Anonymous\BiscuitGenerator;
use TeacherLi07\Anonymous\SlotManager;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateBiscuitController extends AbstractCreateController
{
    public $serializer = BiscuitSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('claimBiscuit');

        $slotManager = resolve(SlotManager::class);
        $available = $slotManager->calculateAvailableSlots($actor);
        $usableCount = Biscuit::where('user_id', $actor->id)->usable()->count();

        if ($usableCount >= $available) {
            throw new \Flarum\Foundation\ValidationException(['biscuits' => 'No available biscuit slots.']);
        }

        $generator = resolve(BiscuitGenerator::class);
        $biscuitString = $generator->generateUnique();

        $hasDefault = Biscuit::where('user_id', $actor->id)
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->exists();

        $biscuit = new Biscuit();
        $biscuit->user_id = $actor->id;
        $biscuit->biscuit_string = $biscuitString;
        $biscuit->is_default = !$hasDefault;
        $biscuit->is_frozen = false;
        $biscuit->save();

        return $biscuit;
    }
}
