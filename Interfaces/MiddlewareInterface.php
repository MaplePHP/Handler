<?php

namespace MaplePHP\Handler\Interfaces;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;

interface MiddlewareInterface
{
    public function before(ResponseInterface $response, RequestInterface $request);
    public function after(ResponseInterface $response, RequestInterface $request);
}
