<?php 

declare(strict_types=1);

namespace PHPFuse\Handler;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Handler\Exceptions\EmitterException;
use PHPFuse\Handler\ErrorHandler;
use PHPFuse\Container\Interfaces\ContainerInterface;
use PHPFuse\Output\SwiftRender;

class Emitter
{
    private $response;
    private $request;
    private $container;
    private $view;
    private $buffer;
    private $stamp;
    private $cacheDefaultTtl = 0;

    private $isGzipped = false;
    private $isBuffer = false;
    private $errorHandler = false;
    private $errorHandlerMsg;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        if(!$this->container->has("view")) {
            $this->container->set("view", '\PHPFuse\Output\SwiftRender');
            $this->view = $this->container->get("view");
            $this->view->setBuffer("0");
        } else {
            $this->view = $this->container->get("view");
        }

        $this->view->setContainer($this->container);
    }

    /**
     * Se a default TTL cache save value (default 0)
     * @param int $ttl In seconds
     */
    function setDefaultCacheTtl(int $ttl) {
    	$this->cacheDefaultTtl = $ttl;
    }

    /**
     * Get SwiftRender instance
     * @return SwiftRender
     */
    function view(): SwiftRender 
    {
        return $this->view;
    }

    /**
     * If you set a buffered response string it will get priorities agains all outher response 
     * @param  string $output
     * @return void
     */
    function outputBuffer(?string $output): void 
    {
        $this->buffer = $output;
    }

    /**
     * Will try to get a output to read
     * @return string
     */
    protected function createResponse() 
    {   
        $stream = $this->response->getBody();
        $this->view->findBind($this->response->getStatusCode());
        $this->isGzipped = $this->isBuffer = false;
        
        if($size = (int)$stream->getSize()) {
            if($stream->isSeekable()) $stream->seek(0);
            return $stream->read($size);

        } else {
            $this->view->setBuffer("\n");
            $this->isBuffer = true;
            if($this->view->exists("index")) {
                return $this->view->index()->get([
                    "statusCode" => $this->response->getStatusCode(),
                    "phraseMessage" => $this->response->getReasonPhrase(),
                    "description" => $this->response->getDescription(),
                    "errorMessage" => $this->errorHandlerMsg,
                    "container" => $this->container
                ]);
            }
        }
        return $this->view->buffer()->get();
    }


    private function getStamp() {
    	if(is_null($this->stamp)) {
    		$this->stamp = ($this->container->has("nonce")) ? $this->container->get("nonce") : time();
    	}
    	return $this->stamp;
    }

    

    /**
     * Build a client response right for all the PSR parameters given 
     * @param  ResponseInterface $response
     * @param  RequestInterface  $request
     * @return void
     */
    function run(ResponseInterface $response, RequestInterface $request): void
    {
        $this->response = $response;
        $this->request = $request;

        $stream = $this->response->getBody();
        if(!is_null($this->buffer)) $stream->write($this->buffer);
        $responseBody = $this->createResponse();


        //Accurate gzip implementation (major improvment for  the apps preformance and load speed)
        if(($acceptEnc = $this->request->getHeader("Accept-Encoding")) && strpos($acceptEnc, 'gzip') !== false) {
            $responseBody = gzencode($responseBody, 9, FORCE_GZIP);
            $this->response = $this->response->withHeader('Content-Encoding', "gzip");
            $this->isGzipped = true;
        }

        // Will only overwrite response if it has changed along the way
        if($this->isBuffer || $this->isGzipped) {
            if($stream->isSeekable()) $stream->seek(0);
            $stream->write($responseBody);
        }

        $size = $stream->getSize();

        if($size) {
            $this->response = $this->response->withHeader('Content-Length', $size);
            // Set cache control if do not exist
            if(!$this->response->hasHeader("Cache-Control")) {
            	if($this->cacheDefaultTtl > 0) {
            		$this->response = $this->response->setCache($this->getStamp(), $this->cacheDefaultTtl);
            	}
            }
        }
        
    	$this->response = $this->response->withHeader("X-Powered-By", 'Fuse-'.$this->getStamp());

        // Will pre execute above headers (Can only be triggered once per instance)
        $this->response->createHeaders();

        // Detached Body from a HEAD request method but keep the meta data intact.
        if($this->request->getMethod() === "HEAD") {
            $stream->seek(0);
            $stream->write("");
        }

        // Will execute all headers if not already created AND execute the status line header
        $this->response->executeHeaders();

        if($stream->isSeekable()) {
            $stream->seek(0);
            echo $stream->read($size);
        }
    }


    /**
     * Makes error handling easy 
     * @param  bool         $displayError  Enables error handlning
     * @param  bool         $niceError     Nice error reporting (true) vs EmitterException (false)
     * @param  bool         $logError      Enable log message (With warning might log even if false with EmitterException, depending on you setup)
     * @param  string|null  $logErrorFile  Path to log file
     * @return void
     */
    function errorHandler(bool $displayError, bool $niceError = true, bool $logError = false, ?string $logErrorFile = NULL): void 
    {
        $this->errorHandler = new ErrorHandler($displayError, $logError, $logErrorFile);
        $this->errorHandler->setHandler(function($msg, $number, $hasError, $displayError) use($niceError) {
            if($hasError) {
                $this->errorHandlerMsg = $msg;
                if($displayError) {
                    if($niceError) {
                        $this->view->findBind(500);
                    } else {
                        throw new EmitterException($this->errorHandlerMsg, $number);
                    }   
                }
            }

        }, $this->errorHandler::CATCH_ALL);
    }

}
