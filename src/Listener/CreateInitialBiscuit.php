<?php

namespace TeacherLi07\Anonymous\Listener;

use Flarum\User\Event\Registered;
use Flarum\User\User;
use Illuminate\Session\Store;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\BiscuitGenerator;

class CreateInitialBiscuit
{
    protected $generator;
    protected $session;

    public function __construct(BiscuitGenerator $generator, Store $session)
    {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function handle(Registered $event): void
    {
        $accountUser = $event->user;

        if (empty($accountUser->is_anonymous_account)) {
            return;
        }

        $biscuitString = $this->generateUniqueBiscuitString();
        $biscuitUser = $this->createBiscuitUser($biscuitString);

        AccountBiscuit::create([
            'account_user_id' => $accountUser->id,
            'biscuit_user_id' => $biscuitUser->id,
            'biscuit_string' => $biscuitString,
            'is_active' => true,
        ]);

        $this->session->put('active_biscuit_user_id', $biscuitUser->id);
    }

    protected function generateUniqueBiscuitString(): string
    {
        $maxRetries = 10;

        for ($i = 0; $i < $maxRetries; $i++) {
            $candidate = $this->generator->generate();

            $exists = AccountBiscuit::where('biscuit_string', $candidate)->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return $this->generator->generate();
    }

    protected function createBiscuitUser(string $biscuitString): User
    {
        $user = User::register(
            $biscuitString,
            $biscuitString . '@anonymous.local',
            bin2hex(random_bytes(16))
        );

        $user->save();

        return $user;
    }
}
