<?php

namespace MaplePHP\Handler\Interfaces;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\UrlInterface;

interface RouterDispatcherInterface
{
    /**
     * Get response instance
     * @return ResponseInterface
     */
    public function response(): ResponseInterface;

    /**
     * Get request instance
     * @return RequestInterface
     */
    public function request(): RequestInterface;

    /**
     * Get url instance (Use this to add some extra custom URL functionallity upon the PSR UriInterface)
     * @return UrlInterface
     */
    public function url(): ?UrlInterface;

    /**
     * Return possible data catched with output buffer
     * @return string
     */
    public function getBufferedResponse(): ?string;

    /**
     * Set URL router dispatch path
     * @param string $path
     * @return void
     */
    public function setDispatchPath(string $path): void;

    /**
     * Cache the router data to a cache file for increased performance.
     * But remember you need to clear the file if you make router changes!
     * @param string $cacheFile
     * @param bool $enableCache (Default true)
     * @return void
     */
    public function setRouterCacheFile(string $cacheFile, bool $enableCache = true): void;

    /**
     * Map a method to a the Router class
     * @param  string|array $methods
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function map($methods, string $pattern, string|array $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function get(string $pattern, string|array $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function post(string $pattern, string|array $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function put(string $pattern, string|array $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function delete(string $pattern, string|array $controller): void;

    /**
     * The will feed the Dispatcher with routes
     * @return callable
     */
    public function dispatcherCallback(): callable;

    /**
     * Dispatch results
     * @param  callable  $call
     * @return ResponseInterface
     */
    public function dispatch(callable $call);
}
