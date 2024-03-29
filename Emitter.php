<?php

declare(strict_types=1);

namespace MaplePHP\Handler;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Handler\Exceptions\EmitterException;
use MaplePHP\Handler\ErrorHandler;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Output\SwiftRender;

class Emitter
{
    private $response;
    private $request;
    private $container;
    private $view;
    private $buffer;
    //private $stamp;
    private $cacheDefaultTtl = 0;

    private $isGzipped = false;
    private $isBuffer = false;
    private $errorHandler = false;
    private $errorHandlerMsg;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        if (!$this->container->has("view")) {
            $this->container->set("view", '\MaplePHP\Output\SwiftRender');
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
    public function setDefaultCacheTtl(int $ttl)
    {
        $this->cacheDefaultTtl = $ttl;
    }

    /**
     * Get SwiftRender instance
     * @return SwiftRender
     */
    public function view(): SwiftRender
    {
        return $this->view;
    }

    /**
     * If you set a buffered response string it will get priorities agains all outher response
     * @param  string $output
     * @return void
     */
    public function outputBuffer(?string $output): void
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

        if ($size = (int)$stream->getSize()) {
            if ($stream->isSeekable()) {
                $stream->seek(0);
            }
            return $stream->read($size);
        } else {
            $this->view->setBuffer("\n");
            $this->isBuffer = true;
            if ($this->view->exists("index")) {
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


    /**
     * Will build the Stream
     * @return StreamInterface
     */
    private function buildStream(): StreamInterface
    {
        $stream = $this->response->getBody();
        if (!is_null($this->buffer)) {
            $stream->write($this->buffer);
        }
        $responseBody = $this->createResponse();


        //Accurate gzip implementation (major improvment for  the apps preformance and load speed)
        if (($acceptEnc = $this->request->getHeaderLine("Accept-Encoding")) && strpos($acceptEnc, 'gzip') !== false) {
            $responseBody = gzencode($responseBody, 9, FORCE_GZIP);
            $this->response = $this->response->withHeader('Content-Encoding', "gzip");
            $this->isGzipped = true;
        }

        // Will only overwrite response if it has changed along the way
        if ($this->isBuffer || $this->isGzipped) {
            if ($stream->isSeekable()) {
                $stream->seek(0);
            }
            $stream->write($responseBody);
        }

        return $stream;
    }

    /**
     * Build a client response right for all the PSR parameters given
     * @param  ResponseInterface $response
     * @param  RequestInterface  $request
     * @return void
     */
    public function run(ResponseInterface $response, RequestInterface $request): void
    {
        $this->response = $response;
        $this->request = $request;

        $stream = $this->buildStream();
        // Look for position instead of size, read has already been triggered once
        $size = $stream->tell();

        if ($size) {
            $this->response = $this->response->withHeader('Content-Length', $size);
            // Set cache control if do not exist
            if (!$this->response->hasHeader("Cache-Control")) {
                // Clear cache on dynamic content is a good standard to make sure
                // that no sensitive content will be cached.
                $this->response = $this->response->clearCache();
            }
        }

        // Will pre execute above headers (Can only be triggered once per instance)
        $this->response->createHeaders();

        // Detached Body from a HEAD request method but keep the meta data intact.
        if ($this->request->getMethod() === "HEAD") {
            $stream->seek(0);
            $stream->write("");
        }

        // Will execute all headers if not already created AND execute the status line header
        $this->response->executeHeaders();

        if ($stream->isSeekable()) {
            $stream->seek(0);
            echo $stream->read((int)$size);
        }
    }

    /**
     * Makes error handling easy
     * @param  bool         $displayError  Enables error handlning
     * @param  bool         $niceError     Nice error reporting (true) vs EmitterException (false)
     * @param  bool         $logError      Enable log message (With warning might log even if
     *                                     false with EmitterException, depending on you setup)
     * @param  string|null  $logErrorFile  Path to log file
     * @return void
     */
    public function errorHandler(
        bool $displayError,
        bool $niceError = true,
        bool $logError = false,
        ?string $logErrorFile = null
    ): void {
        $this->errorHandler = new ErrorHandler($displayError, $logError, $logErrorFile);
        $this->errorHandler->setHandler(function ($msg, $number, $hasError, $displayError) use ($niceError) {
            if ($hasError) {
                $this->errorHandlerMsg = $msg;
                if ($displayError) {
                    if ($niceError) {
                        $this->view->findBind(500);
                    } else {
                        throw new EmitterException($this->errorHandlerMsg, $number);
                    }
                }
            }
        }, $this->errorHandler::CATCH_ALL);
    }
}
