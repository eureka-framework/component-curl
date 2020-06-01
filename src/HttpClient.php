<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl;

use Eureka\Component\Curl\Exception\CurlException;
use Eureka\Component\Curl\Exception\HttpClientException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpClient implements ClientInterface
{
    /** @var string[][] */
    private $responseHeaders = [];

    /** @var Psr17Factory $httpFactory */
    private $httpFactory;

    /** @var int $timeout */
    private $timeout;

    /** @var int $connectTimeout */
    private $connectTimeout;

    /**
     * HttpClient constructor.
     *
     * @param int $timeout
     * @param int $connectTimeout
     */
    public function __construct(int $timeout = 10, int $connectTimeout = 10)
    {
        $this->timeout        = $timeout;
        $this->connectTimeout = $connectTimeout;

        $this->httpFactory    = new Psr17Factory();
    }

    /**
     * Send request through curl & get response with stream as body response.
     *
     * @param RequestInterface $request
     * @param int $connectTimeout
     * @param int $timeout
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request, int $connectTimeout = 30, int $timeout = 60): ResponseInterface
    {
        try {
            $streamResource = fopen('php://temp', 'w+');

            $curl = $this->getCurl($request, $streamResource);

            //~ Execute curl
            $exec = $curl->exec();

            //~ Read status (for response)
            $info   = $curl->getInfo();
            $status = (isset($info['http_code']) ? (int) $info['http_code'] : 200);

            //~ Check return
            if (false === $exec) {
                $error = $curl->getError();
                $errno = $curl->getErrorNumber();

                //~ Close connection & stream
                $curl->close();
                fclose($streamResource);

                throw new HttpClientException(
                    sprintf('Execution failed ! (error: %s, code: %d, infos: %s)', $error, $errno, json_encode($curl->getInfo())),
                    $errno
                );
            }

            $curl->close();

            // Create stream & move pointer after header position in "stream".
            $stream = $this->httpFactory->createStreamFromResource($streamResource);
            $stream->seek(isset($info['header_size']) ? (int) $info['header_size'] : 0);
        } catch (CurlException $exception) {
            throw new HttpClientException($exception->getMessage(), $exception->getCode());
        }


        return $this->createResponse($status, $stream);
    }

    /**
     * Read header line from curl response.
     *
     * @param resource $resource Curl resource
     * @param string $header
     * @return int
     */
    public function readResponseHeader($resource, string $header): int
    {
        $pos = strpos($header, ':');

        if ($pos !== false) {
            $name  = trim(substr($header, 0, $pos));
            $value = trim(substr($header, $pos + 1));

            if (!isset($this->responseHeaders[$name])) {
                $this->responseHeaders[$name] = [];
            }

            $this->responseHeaders[$name][] = $value;
        }

        return strlen($header);
    }

    /**
     * @param RequestInterface $request
     * @param resource $streamResource
     * @return Curl
     * @throws Exception\CurlInitException
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlUnexpectedValueException
     */
    private function getCurl(RequestInterface $request, $streamResource): Curl
    {
        $curl = new Curl();
        $curl
            ->init((string) $request->getUri())
            ->setMethod($request->getMethod())
        ;

        //~ Main options
        $curl
            ->setOption(CURLOPT_RETURNTRANSFER, false)
            ->setOption(CURLOPT_CONNECTTIMEOUT, $this->connectTimeout)
            ->setOption(CURLOPT_TIMEOUT, $this->timeout)
            ->setOption(CURLOPT_FILE, $streamResource)
            ->setOption(CURLOPT_HEADER, true)
            ->setOption(CURLOPT_HEADERFUNCTION, [$this, 'readResponseHeader'])
            ->setOption(CURLOPT_FOLLOWLOCATION, true)
            ->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0')
        ;

        //~ Request headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . implode(', ', $value);
        }

        if (!empty($headers)) {
            $curl->setOption(CURLOPT_HTTPHEADER, $headers);
        }

        //~ Request body if necessary
        if ($request->getMethod() === 'POST' && $request->getBody() instanceof StreamInterface) {
            $request->getBody()->rewind();
            $curl->setOption(CURLOPT_POSTFIELDS, $request->getBody()->getContents());
        }

        return $curl;
    }

    /**
     * @param int $status
     * @param StreamInterface $stream
     * @return ResponseInterface
     */
    private function createResponse(int $status, StreamInterface $stream): ResponseInterface
    {
        return new Response($status, $this->responseHeaders, $stream);
    }
}
