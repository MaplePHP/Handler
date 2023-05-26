<?php

namespace PHPFuse\Handler\Interfaces;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;

interface MiddlewareInterface
{
    public function before(ResponseInterface $response, RequestInterface $request);
    public function after(ResponseInterface $response, RequestInterface $request);
}
