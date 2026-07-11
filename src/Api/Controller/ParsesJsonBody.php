<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Psr\Http\Message\ServerRequestInterface;

trait ParsesJsonBody
{
    protected function getRequestBody(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        if (is_array($body)) {
            return $body;
        }

        $raw = (string) $request->getBody();
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
