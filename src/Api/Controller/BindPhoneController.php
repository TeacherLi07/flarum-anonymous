<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;

class BindPhoneController extends AbstractShowController
{
    public $serializer = CurrentUserSerializer::class;

    protected function data(ServerRequestInterface $request, $document)
    {
        $actor = RequestUtil::getActor($request);
        $accountUserId = session('account_id');

        if (! $accountUserId) {
            throw new \Flarum\User\Exception\PermissionDeniedException();
        }

        $accountUser = User::findOrFail($accountUserId);
        $body = $request->getParsedBody();
        $attributes = $body['data']['attributes'] ?? [];

        $phone = $attributes['phone'] ?? null;
        $code = $attributes['verificationCode'] ?? null;

        if (! $phone || ! $code) {
            throw new \RuntimeException('Phone and verification code are required.');
        }

        $sms = resolve(\TeacherLi07\Anonymous\Auth\SmsService::class);

        if (! $sms->verify($phone, $code)) {
            throw new \RuntimeException('Invalid or expired verification code.');
        }

        if (User::where('phone', $phone)->where('id', '!=', $accountUser->id)->exists()) {
            throw new \RuntimeException('This phone number is already registered.');
        }

        $accountUser->phone = $phone;
        $accountUser->phone_verified_at = now();
        $accountUser->save();

        return $actor;
    }
}
