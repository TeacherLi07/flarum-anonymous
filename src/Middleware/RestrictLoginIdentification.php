<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Middleware;

use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RestrictLoginIdentification implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        $isLogin = ($method === 'POST' && str_ends_with($path, '/login'))
                || ($method === 'POST' && str_ends_with($path, '/api/token'));

        if ($isLogin) {
            $body = $request->getParsedBody();
            $identification = Arr::get($body, 'identification', '');

            if (empty($identification)) {
                return $handler->handle($request);
            }

            // Allow email
            if (str_contains($identification, '@')) {
                return $handler->handle($request);
            }

            // Allow phone (digits only, 6-20 chars)
            if (preg_match('/^\d{6,20}$/', $identification)) {
                $user = User::where('phone', $identification)->first();
                if ($user) {
                    $body['identification'] = $user->email ?: $user->username;
                    $request = $request->withParsedBody($body);
                }
                return $handler->handle($request);
            }

            // Reject plain username
            throw new \Flarum\Foundation\ValidationException([
                'identification' => 'Please use phone number or email to login. Username login is disabled.',
            ]);
        }

        return $handler->handle($request);
    }
}
