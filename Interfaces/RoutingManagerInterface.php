<?php

namespace PHPFuse\Emitter\Interfaces;


interface RoutingManagerInterface
{


    /**
     * Start Manager a Route
     * @param mixed $method    
     * @param mixed $pattern   
     * @param mixed $controller
     */
    public function __construct($method, $pattern, $controller);

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
