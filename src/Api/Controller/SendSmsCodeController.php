<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Huihu\Anonymous\Api\Controller;

use Huihu\Anonymous\Auth\SmsService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Support\Arr;

class SendSmsCodeController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $phone = Arr::get($body, 'phone');

        if (!$phone || !preg_match('/^\d{6,20}$/', $phone)) {
            throw new \RuntimeException('Invalid phone number.');
        }

        $smsService = resolve(SmsService::class);
        $smsService->send($phone);

        return new EmptyResponse(204);
    }
}
