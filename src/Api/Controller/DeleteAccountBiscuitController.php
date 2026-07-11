<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\BiscuitGenerator;
use Flarum\User\User;

class DeleteAccountBiscuitController extends AbstractDeleteController
{
    protected $generator;

    public function __construct(BiscuitGenerator $generator)
    {
        $this->generator = $generator;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        $biscuit = AccountBiscuit::findOrFail($id);
        $accountUserId = $request->getAttribute('session')->get('account_id');

        if (! $accountUserId || (int) $biscuit->account_user_id !== (int) $accountUserId) {
            if (! $actor->isAdmin()) {
                throw new \Flarum\User\Exception\PermissionDeniedException();
            }
        }

        $wasActive = $biscuit->is_active;
        $biscuit->is_active = false;
        $biscuit->save();
        $biscuit->delete();

        if ($wasActive) {
            $alternative = AccountBiscuit::where('account_user_id', $biscuit->account_user_id)
                ->usable()
                ->first();

            if ($alternative) {
                $alternative->is_active = true;
                $alternative->save();
                $request->getAttribute('session')->put('active_biscuit_user_id', $alternative->biscuit_user_id);
            } else {
                $biscuitString = $this->generateUniqueBiscuitString();
                $biscuitUser = User::register(
                    $biscuitString,
                    $biscuitString . '@anonymous.local',
                    bin2hex(random_bytes(16))
                );
                $biscuitUser->is_email_confirmed = true;
                $biscuitUser->save();

                $newBiscuit = AccountBiscuit::create([
                    'account_user_id' => $biscuit->account_user_id,
                    'biscuit_user_id' => $biscuitUser->id,
                    'biscuit_string' => $biscuitString,
                    'is_active' => true,
                ]);

                $request->getAttribute('session')->put('active_biscuit_user_id', $biscuitUser->id);
            }
        }
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
