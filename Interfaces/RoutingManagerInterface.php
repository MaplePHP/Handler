<?php

namespace MaplePHP\Handler\Interfaces;

interface RoutingManagerInterface
{
    /**
     * Start Manager a Route, USED to make it easier to change out router library
     * @param string|array          $method     (GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE)
     * @param string                $pattern
     * @param string|array|callable $controller
     */
    public function __construct(string|array $method, string $pattern, string|array|callable $controller);

    /**
     * Get method
     * @return string|array
     */
    public function getMethod();

    /**
     * Get Pattern
     * @return string
     */
    public function getPattern();

    /**
     * Get Controller
     * @return mixed
     */
    public function getController();
}
