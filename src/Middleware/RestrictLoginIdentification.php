<?php

namespace TeacherLi07\Anonymous\Middleware;

use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RestrictLoginIdentification implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        $isToken = in_array($path, ['/token', '/api/token', 'token']);

        if ($isToken && $method === 'POST') {
            $body = $request->getParsedBody();
            $identification = $body['identification'] ?? null;

            if ($identification) {
                $isEmail = filter_var($identification, FILTER_VALIDATE_EMAIL);

                if (preg_match('/^\d{6,20}$/', $identification)) {
                    $user = User::where('phone', $identification)->first();

                    if ($user) {
                        $body['identification'] = $user->email;

                        $request = $request->withParsedBody($body);
                    }
                }

                if (! $isEmail && ! preg_match('/^\d{6,20}$/', $identification)) {
                    return new \Laminas\Diactoros\Response\JsonResponse([
                        'errors' => [[
                            'code' => 'invalid_login',
                            'detail' => 'Please log in with your phone number or email.',
                        ]],
                    ], 401);
                }
            }
        }

        return $handler->handle($request);
    }
}
