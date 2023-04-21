<?php 

declare(strict_types=1);

namespace PHPFuse\Emitter;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Emitter\Exceptions\EmitterException;
use PHPFuse\Emitter\ErrorHandler;
use PHPFuse\Container\Interfaces\ContainerInterface;
use PHPFuse\Output\SwiftRender;

class Emitter
{
    private $response;
    private $request;
    private $container;
    private $view;
    private $buffer;
    private $attr;

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
    }

    /**
     * GEt 
     * @return [type] [description]
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
                    "errorMessage" => $this->errorHandlerMsg,
                    "container" => $this->container
                ]);
            }
        }
        return $this->view->buffer()->get();
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
        if($size) $this->response = $this->response->withHeader('Content-Length', $size);

        $this->response->createHeaders();

        // Detached Body from a HEAD request method but keep the meta data intact.
        if($this->request->getMethod() === "HEAD") {
            $stream->seek(0);
            $stream->write("");
        }

        $statusLine = sprintf('HTTP/%s %s %s', $this->response->getProtocolVersion(), $this->response->getStatusCode(), $this->response->getReasonPhrase());
        header($statusLine, true, $this->response->getStatusCode());

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
