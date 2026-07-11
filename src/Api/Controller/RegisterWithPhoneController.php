<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use TeacherLi07\Anonymous\AccountBiscuit;
use TeacherLi07\Anonymous\Api\Serializer\AccountBiscuitSerializer;
use TeacherLi07\Anonymous\Auth\SmsService;
use TeacherLi07\Anonymous\BiscuitGenerator;

class RegisterWithPhoneController extends AbstractCreateController
{
    public $serializer = \Flarum\Api\Serializer\CurrentUserSerializer::class;

    protected $sms;
    protected $generator;

    public function __construct(SmsService $sms, BiscuitGenerator $generator)
    {
        $this->sms = $sms;
        $this->generator = $generator;
    }

    protected function data(ServerRequestInterface $request, $document)
    {
        $body = $request->getParsedBody();
        $attributes = Arr::get($body, 'data.attributes', []);

        $phone = $attributes['phone'] ?? null;
        $code = $attributes['verificationCode'] ?? null;
        $password = $attributes['password'] ?? null;

        if (! $phone || ! $code || ! $password) {
            throw new \RuntimeException('Phone, verification code, and password are required.');
        }

        if (! $this->sms->verify($phone, $code)) {
            throw new \RuntimeException('Invalid or expired verification code.');
        }

        // Check if phone already registered
        if (User::where('phone', $phone)->exists()) {
            throw new \RuntimeException('This phone number is already registered.');
        }

        $username = substr(sha1($phone), 0, 30);

        $accountUser = new User();
        $accountUser->username = $username;
        $accountUser->email = $phone . '@anonymous.local';
        $accountUser->password = $password;
        $accountUser->phone = $phone;
        $accountUser->phone_verified_at = now();
        $accountUser->is_anonymous_account = true;
        $accountUser->is_email_confirmed = true;
        $accountUser->joined_at = now();
        $accountUser->save();

        session()->put('account_id', $accountUser->id);

        // CreateInitialBiscuit listener handles biscuit creation
        event(new \Flarum\User\Event\Registered($accountUser));

        return $accountUser;
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
