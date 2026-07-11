<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;
use TeacherLi07\Anonymous\BiscuitGenerator;
use TeacherLi07\Anonymous\SlotManager;

class CreateAccountBiscuitController extends AbstractCreateController
{
    public $serializer = AccountBiscuitSerializer::class;

    protected $generator;
    protected $slotManager;

    public function __construct(BiscuitGenerator $generator, SlotManager $slotManager)
    {
        $this->generator = $generator;
        $this->slotManager = $slotManager;
    }

    protected function data(ServerRequestInterface $request, $document)
    {
        $actor = RequestUtil::getActor($request);
        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId) {
            throw new \Flarum\User\Exception\PermissionDeniedException();
        }

        $accountUser = User::findOrFail($accountUserId);

        $usableCount = AccountBiscuit::where('account_user_id', $accountUserId)
            ->usable()
            ->count();

        $availableSlots = $this->slotManager->calculateAvailableSlots($accountUser);

        if ($usableCount >= $availableSlots) {
            throw new \RuntimeException('No available biscuit slots.');
        }

        $biscuitString = $this->generateUniqueBiscuitString();
        $biscuitUser = User::register(
            $biscuitString,
            $biscuitString . '@anonymous.local',
            bin2hex(random_bytes(16))
        );
        $biscuitUser->save();

        $note = Arr::get($request->getParsedBody(), 'data.attributes.note');

        $biscuit = AccountBiscuit::create([
            'account_user_id' => $accountUserId,
            'biscuit_user_id' => $biscuitUser->id,
            'biscuit_string' => $biscuitString,
            'note' => $note,
            'is_active' => false,
        ]);

        return $biscuit;
    }

    protected function generateUniqueBiscuitString(): string
    {
        $maxRetries = 10;

        for ($i = 0; $i < $maxRetries; $i++) {
            $candidate = $this->generator->generate();

            if (! AccountBiscuit::where('biscuit_string', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $this->generator->generate();
    }
}
