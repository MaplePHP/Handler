<?php


declare(strict_types=1);

namespace PHPFuse\Handler;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Handler\Interfaces\RouterDispatcherInterface;
use PHPFuse\Handler\RoutingManager;
use PHPFuse\Container\Reflection;

use PHPFuse\Http\Interfaces\UrlInterface;
use PHPFuse\Http\Url;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class RouterDispatcher implements RouterDispatcherInterface
{
    public const FOUND = Dispatcher::FOUND;
    public const NOT_FOUND = Dispatcher::NOT_FOUND;
    public const METHOD_NOT_ALLOWED = Dispatcher::METHOD_NOT_ALLOWED;

    private $url;
    private $request;
    private $router;
    private $routerCacheFile;
    private $enableCache;
    private $dispatcher;
    private $dispatchPath;
    private $buffer;
    private $response;
    private $method;

    private static $middleware;

    /**
     * Router Dispatcher, Used to make it easier to change out router library
     */
    public function __construct(ResponseInterface $response, RequestInterface $request)
    {
        $this->response = $response;
        $this->request = $request;
        $this->method = $this->request->getMethod();
    }

    /**
     * Get response instance
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get request instance
     * @return RequestInterface
     */
    public function request(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get url instance
     * @return UrlInterface
     */
    public function url(): ?UrlInterface
    {
        if (is_null($this->url)) {
            $this->url = $this->setUrl();
        }
        return $this->url;
    }

    public function setUrl(array $param = array(), string $dir = "")
    {
        return new Url($this->request, $param);
    }

    /**
     * Return possible data catched with output buffer
     * @return string
     */
    public function getBufferedResponse(): ?string
    {
        return $this->buffer;
    }

    /**
     * Change request method to a static method
     * @param void
     */
    public function setRequestMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * Set URL router dispatch path
     * @param void
     */
    public function setDispatchPath(string $path): void
    {
        $this->dispatchPath = $path;
    }

    /**
     * Set possible full directory path to file
     * @param void
     */
    public function setRouterCacheFile(string $cacheFile, bool $enableCache = true): void
    {
        $this->routerCacheFile = $cacheFile;
        $this->enableCache = $enableCache;
        $dir = dirname($this->routerCacheFile);

        if (!is_writable($dir)) {
            throw new EmitterException("Directory (\"{$dir}/\") is not writable. ".
                "Could not save \"{$this->routerCacheFile}\" file.", 1);
        }
    }


    /**
     * The is used to nest group routes
     * The param "Order" here is important.
     * Callable: Routes to be binded to pattern or middelwares
     * Pattern: Routes binded to pattern
     * Array: Middelwares
     * @param  mixed  $a Callable/Pattern
     * @param  mixed  $b Callable/array
     * @param  array  $c array
     * @return void
     */
    public function group($a, $b, $c = array()): void
    {
        $inst = clone $this;
        $inst->router = array();
        $pattern = (is_string($a)) ? $a : null;
        $call = ($pattern) ? $b : $a;
        $data = ($pattern) ? $c : $b;

        if (!is_array($data)) {
            $data = [];
        }
        if (!is_callable($call)) {
            throw new \InvalidArgumentException("Either the argumnet 1 or 2 need to be callable.", 1);
        }

        $this->router[] = function () use (&$inst, $pattern, $call, $data) {
            $call($inst, $data);
            return [
                "router" => $inst,
                "data" => $data,
                "pattern" => $pattern
            ];
        };
    }

    /**
     * Map a request method and attach controller and it's pattern to RoutingManagerInterface
     * @param  string|array $methods    (GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE)
     * @param  string $pattern          Example: /about, /{page:about}, /{page:.+}, /{category:[^/]+}, /{id:\d+}
     * @param  string|array $controller Attach a controller (['Name\Space\ClassName', 'methodName'])
     * @return void
     */
    public function map($methods, string $pattern, $controller): void
    {
        $this->router[] = new RoutingManager($methods, $pattern, $controller);
    }

    /**
     * Map GET method router and attach controller to it's pattern
     * @param  string $pattern          Example: /about, /{page:about}, /{page:.+}, /{category:[^/]+}, /{id:\d+}
     * @param  string|array $controller Attach a controller (['Name\Space\ClassName', 'methodName'])
     * @return void
     */
    public function get(string $pattern, $controller): void
    {
        $this->map("GET", $pattern, $controller);
    }

    /**
     * Map POST method router and attach controller to it's pattern
     * @param  string $pattern          Example: /about, /{page:about}, /{page:.+}, /{category:[^/]+}, /{id:\d+}
     * @param  string|array $controller Attach a controller (['Name\Space\ClassName', 'methodName'])
     * @return void
     */
    public function post(string $pattern, $controller): void
    {
        $this->map("POST", $pattern, $controller);
    }

    /**
     * Map PUT method router and attach controller to it's pattern (Se GET/POST for example)
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function put(string $pattern, $controller): void
    {
        $this->map("PUT", $pattern, $controller);
    }

    /**
     * Map DELETE method router and attach controller to it's pattern (Se GET/POST for example)
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function delete(string $pattern, $controller): void
    {
        $this->map("DELETE", $pattern, $controller);
    }

    /**
     * Create a shell/cli route
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function shell(string $pattern, $controller): void
    {
        $this->map("CLI", $pattern, $controller);
    }

    /**
     * Create a shell/cli route
     * @param  string $pattern
     * @param  string|array $controller
     * @return void
     */
    public function cli(string $pattern, $controller): void
    {
        $this->shell($pattern, $controller);
    }

    /**
     * The will feed the Dispatcher with routes
     * @return callable
     */
    public function dispatcherCallback(): callable
    {
        return function (RouteCollector $route) {

            foreach ($this->router as $r) {
                if (is_callable($r)) {
                    $inst = $r();
                    $this->dispatcherNest($route, $inst);
                } else {
                    $route->addRoute($r->getMethod(), $r->getPattern(), $r->getController());
                }
            }
        };
    }

    /**
     * Register the dispatcher
     * @return Dispatcher
     */
    protected function registerDispatcher(): Dispatcher
    {
        if (is_null($this->dispatcher)) {
            if (is_null($this->routerCacheFile)) {
                $this->dispatcher = \FastRoute\simpleDispatcher($this->dispatcherCallback());
            } else {
                $this->dispatcher = \FastRoute\cachedDispatcher($this->dispatcherCallback(), [
                    'cacheFile' => $this->routerCacheFile,
                    'cacheDisabled' => !$this->enableCache
                ]);
            }
        }
        return $this->dispatcher;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function loadMid()
    {
    }

    /**
     * Dispatch results
     * @return void
     */
    public function dispatch(callable $call)
    {
        $dispatcher = $this->registerDispatcher();
        $routeInfo = $dispatcher->dispatch($this->method, $this->dispatchPath);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $this->url = $this->setUrl($routeInfo[2]);
            $call($routeInfo[0], $this->response, $this->request, $this->url);

            //ob_start();
            if (is_array($routeInfo[1]['controller'])) {
                $this->dispatchMiddleware(($routeInfo[1]['data'] ?? null), function () use (&$response, $routeInfo) {
                    $select = (isset($routeInfo[1]['controller'])) ? $routeInfo[1]['controller'] : $routeInfo[1];
                    $reflect = new Reflection($select[0]);
                    $controller = $reflect->dependencyInjector();

                    if (isset($select[1])) {
                        $response = $controller->{$select[1]}($this->response, $this->request);
                    } else {
                        $response = $controller($this->response, $this->request);
                    }

                    if ($response instanceof ResponseInterface) {
                        $this->response = $response;
                    }
                });
            } else {
                $response = $routeInfo[1]['controller']($this->response, $this->request);
            }

        //$this->buffer = ob_get_clean();
        } else {
            $response = $call($routeInfo[0], $this->response, $this->request, null);
        }

        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        }



        return $this->response;
    }

    /**
     * @dispatcherNest will handle all nested grouped routes
     * @param  RouteCollector   $route
     * @param  array            $inst
     * @return void
     */
    protected function dispatcherNest(RouteCollector $route, array $inst): void
    {
        if (!is_null($inst['pattern'])) {
            $route->addGroup($inst['pattern'], function (RouteCollector $route) use ($inst) {
                foreach ($inst['router']->router as $g) {
                    if (($g instanceof RoutingManager)) {
                        $route->addRoute($g->getMethod(), $g->getPattern(), $g->getMiddleware($inst['data']));
                    } else {
                        if (is_callable($g)) {
                            $newInst = $g();
                            $newInst['data'] = array_merge($inst['data'], $newInst['data']);
                            $this->dispatcherNest($route, $newInst, true);
                        }
                    }
                }
            });
        } else {
            foreach ($inst['router']->router as $k => $g) {
                if (($g instanceof RoutingManager)) {
                    $route->addRoute($g->getMethod(), $g->getPattern(), $g->getMiddleware($inst['data']));
                } else {
                    if (is_callable($g)) {
                        $newInst = $g();
                        $newInst['data'] = array_merge($inst['data'], $newInst['data']);
                        $this->dispatcherNest($route, $newInst, true);
                    }
                }
            }
        }
    }

    /**
     * Will dispatch the middleware at the right position
     * @param  ?array   $data List of middlewares
     * @param  callable $call inject routers
     * @return void
     */
    protected function dispatchMiddleware(?array $data, $call): void
    {
        $middleware = array();
        if (!is_null($data)) {
            foreach ($data as $c) {
                $d = $this->getClass($c);
                if (!isset(self::$middleware[$d[0]])) {
                    $reflect = new Reflection($d[0]);
                    self::$middleware[$d[0]] = $reflect->dependencyInjector();
                }
                $r = self::$middleware[$d[0]]->before($this->response, $this->request);
                if (!is_null($d[1])) {
                    $r = self::$middleware[$d[0]]->{$d[1]}($this->response, $this->request);
                } // Is method set:
                if ($r instanceof ResponseInterface) {
                    $this->response = $r;
                }
            }
        }
        $call(); // Possible controller data
        if (is_array(self::$middleware)) {
            foreach (self::$middleware as $m) {
                $r = $m->after($this->response, $this->request);
                if ($r instanceof ResponseInterface) {
                    $this->response = $r;
                }
            }
        }
    }

    /**
     * Extract class and mthod from argument
     * @param  string|arrat $classMethod
     * @return array
     */
    protected function getClass($classMethod): array
    {
        $class = $classMethod;
        $method = null;
        if (is_array($classMethod)) {
            $class = $classMethod[0];
            $method = ($classMethod[1] ?? null);
        }
        return [$class, $method];
    }
}
