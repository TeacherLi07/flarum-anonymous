<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TeacherLi07\Anonymous\Auth\SmsService;

class SendSmsCodeController implements RequestHandlerInterface
{
    protected $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $phone = $body['phone'] ?? null;

        if (! $phone) {
            return new EmptyResponse(400);
        }

        $this->sms->send($phone);

        return new EmptyResponse(204);
    }
}
