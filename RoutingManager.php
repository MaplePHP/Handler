<?php 

declare(strict_types=1);

namespace PHPFuse\Emitter;

use PHPFuse\Emitter\Interfaces\RoutingManagerInterface;
use InvalidArgumentException;

class RoutingManager implements RoutingManagerInterface
{


    private $method;
    private $pattern;
    private $controller;

    /**
     * Start Manager a Route, USED to make it easier to change out router library
     * @param string|array  $method     (GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE)
     * @param string $pattern
     * @param string|array $controller
     */
    function __construct($method, $pattern, $controller)
    {   
        $this->setMethod($method);
        $this->pattern = $pattern;
        $this->setController($controller);
    }


    /**
     * Sets a valid request Method
     * @param void
     */
    protected function setMethod($method): void 
    {
        if(is_array($method)) {
            if(count($method) <= 0) throw new InvalidArgumentException("Method array can not be empty", 1);
            $method = array_map('strtoupper', $method);
        } else {
            $method = strtoupper($method);
        }

        $this->method = $method;
    }

     /**
     * Sets a valid Pattern
     * @param void
     */
    protected function setPattern(string $pattern): void 
    {
        $this->pattern = $pattern;
    }

    /**
     * Sets a valid request Controller
     * @param void
     */
    protected function setController($controller): void 
    {
        $isArr = false;
        if(is_string($controller)) $controller = [$controller];
        if(!($isArr = is_array($controller)) && !is_callable($controller)) throw new InvalidArgumentException("Controller needs to be string or array", 1);
        if($isArr && count($controller) <= 0) throw new InvalidArgumentException("Method array can not be empty", 1);
        $this->controller = $controller;
    }

    /**
     * Get method
     * @return string|array
     */
    public function getMethod() 
    {
        return $this->method;
    }

    /**
     * Get Pattern
     * @return string
     */
    public function getPattern(): string 
    {
        return $this->pattern;
    }

    /**
     * Get Controller
     * @return mixed
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * Get Controller
     * @return mixed
     */
    public function getMiddleware($data) {
        return [
            "controller" => $this->controller,
            "data" => $data
        ];
    }
}
