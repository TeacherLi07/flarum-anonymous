<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Middleware;

use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InjectBiscuitToPostData implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        if ($method === 'POST' && $this->isPostEndpoint($path)) {
            $body = $request->getParsedBody();

            if (is_array($body)) {
                $metaBiscuit = Arr::get($body, 'meta.biscuit_string');

                if ($metaBiscuit) {
                    Arr::set($body, 'data.attributes.biscuit_string', $metaBiscuit);
                    $request = $request->withParsedBody($body);
                }
            }
        }

        return $handler->handle($request);
    }

    private function isPostEndpoint(string $path): bool
    {
        return str_contains($path, '/api/posts') || str_contains($path, '/api/discussions');
    }
}
