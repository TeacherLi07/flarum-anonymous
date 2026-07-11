<?php

namespace TeacherLi07\Anonymous\Api\Controller;

use Psr\Http\Message\ServerRequestInterface;

trait SessionFromRequest
{
    protected function sessionFromRequest(ServerRequestInterface $request)
    {
        return $request->getAttribute('session');
    }
}
