<?php

namespace PHPFuse\Handler\Interfaces;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\UrlInterface;

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
     * @param void
     */
    public function setDispatchPath(string $path): void;

    /**
     * Set possible full directory path to file
     * @param void
     */
    public function setRouterCacheFile(string $cacheFile, bool $enableCache = true): void;

    /**
     * Map a method to a the Router class
     * @param  string|array $methods
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function map($methods, string $pattern, $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function get(string $pattern, $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function post(string $pattern, $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function put(string $pattern, $controller): void;

    /**
     * Should create a shortcut to the Map method
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function delete(string $pattern, $controller): void;


    /**
     * The will feed the Dispatcher with routes
     * @return callable
     */
    public function dispatcherCallback(): callable;


    /**
     * Dispatch results
     * @return void
     */
    public function dispatch(callable $call);
}
