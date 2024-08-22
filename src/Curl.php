<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl;

use Eureka\Component\Curl\Exception\CurlInitException;

/**
 * Class wrapper for native php curl function.
 *
 * @author Romain Cottard
 */
class Curl
{
    protected \CurlHandle|null $connection = null;

    protected ?string $message = null;

    /** @var array<string[][]|float|int|string|null> $curlInfo Curl info data */
    protected array $curlInfo = [];

    /** @var array<mixed> $defaultOptions Default options used */
    protected array $defaultOptions = [];

    /**
     * Class constructor
     *
     * @param null|string $url
     * @throws Exception\CurlInitException
     */
    public function __construct(string $url = null)
    {
        $this->init($url);
    }

    /**
     * Initialize curl connection.
     *
     * @param string|null $url Url for connection.
     * @return $this
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function init(string $url = null): self
    {
        $connection = empty($url) ? curl_init() : curl_init($url);

        if ($connection === false) {
            throw new CurlInitException('Initialization failed !');
        }

        $this->connection = $connection;

        return $this;
    }

    /**
     * Close connection.
     *
     * @return $this
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function close(): self
    {
        curl_close($this->getConnection());

        return $this;
    }

    /**
     * Execute cURL command.
     *
     * @return bool|string
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function exec(): bool|string
    {
        $result = curl_exec($this->getConnection());
        $info   = curl_getinfo($this->getConnection());

        if ($info !== false) {
            $this->curlInfo = (array) $info;
        }

        return $result;
    }

    /**
     * Get last cURL error message.
     *
     * @return string Error message.
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function getError(): string
    {
        if (null !== $this->message) {
            $message       = $this->message;
            $this->message = null;

            return $message;
        }

        return curl_error($this->getConnection());
    }

    /**
     * Get last cURL error number.
     *
     * @return int Error number.
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function getErrorNumber(): int
    {
        return curl_errno($this->getConnection());
    }

    /**
     * Return curl infos stored of exist, else wrap curl_getinfo()
     *
     * @return array<string[][]|float|int|string|null> Array of data about previous curl request.
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function getInfo(): array
    {
        if (!empty($this->curlInfo)) {
            return $this->curlInfo;
        }

        return (array) curl_getinfo($this->getConnection());
    }

    /**
     * Check if previous request is success or not
     * (success: no error & http code is 2XX or 3XX)
     *
     * @return bool
     * @throws CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function isSuccess(): bool
    {
        $info = $this->getInfo();

        if ($this->getErrorNumber() > 0) {
            $this->message = $this->getError();
            return false;
        }

        if (!isset($info['http_code']) || !is_string($info['http_code'])) {
            $this->message = 'No information about "http_code"!';
            return false;
        }

        $httpCodeFirstNumber = substr($info['http_code'], 0, 1);
        if (!in_array($httpCodeFirstNumber, ['2', '3'])) {
            $this->message = '"http_code" is not a 2XX or 3XX status code! http-code: ' . $info['http_code'];
            return false;
        }

        $length = (int) ($info['download_content_length'] ?? 0);
        $size   = (int) ($info['size_download'] ?? 0);
        if ($length !== $size) {
            $this->message = 'Transfer did not complete!';
            return false;
        }

        return empty($this->message);
    }

    /**
     * Override default option (array or name/value)
     *
     * @param array<mixed>|string $name Name or array of options to set.
     * @param mixed|null $value Value to set.
     * @return $this
     *
     * @codeCoverageIgnore
     */
    public function setOptionDefault(array|string $name, mixed $value = null): self
    {
        if (is_array($name)) {
            $this->defaultOptions = $name + $this->defaultOptions;
        } else {
            $this->defaultOptions[$name] = $value;
        }

        return $this;
    }

    /**
     * Set option (array or name/value)
     *
     * @param array<mixed>|int $name Name or array of options to set.
     * @param mixed|null $value Value to set.
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setOption(array|int $name, mixed $value = null): self
    {
        if (is_array($name)) {
            return $this->setOptions($name);
        }

        $openBasedir = ini_get('openBasedir');

        if ($name == CURLOPT_FOLLOWLOCATION && (!empty($openBasedir))) {
            $value = false;
        }

        if (false === curl_setopt($this->getConnection(), $name, $value)) {
            $error = $this->getError();
            $errno = $this->getErrorNumber();
            $this->close();
            throw new Exception\CurlOptionException("Set option failed ! (error: $error)", $errno);
        }

        return $this;
    }

    /**
     * Set option (array or name/value)
     *
     * @param array<mixed> $options Array of options to set.
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    /**
     * Sets the POST data
     *
     * @param array<mixed>|string $data An array of key => value pairs, or an urlencoded string
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setPostData(array|string $data): self
    {
        return $this->setOption(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * Sets the URL for the request
     *
     * @param string $url The URL
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setUrl(string $url): self
    {
        return $this->setOption(CURLOPT_URL, $url);
    }

    /**
     * Configures the return behavior.
     *
     * @param bool $return If true, exec() should return the response content.
     * Otherwise, the response content will be sent to the output stream.
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setReturn(bool $return): self
    {
        return $this->setOption(CURLOPT_RETURNTRANSFER, $return);
    }

    /**
     * Sets the request HTTP method
     *
     * @param string $method The method name (GET, POST, PUT or DELETE)
     * @return $this
     * @throws Exception\CurlUnexpectedValueException
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     *
     * @codeCoverageIgnore
     */
    public function setMethod(string $method): self
    {
        $method = strtoupper($method);

        $options = [
            CURLOPT_PUT           => null,
            CURLOPT_POST          => null,
            CURLOPT_CUSTOMREQUEST => null,
        ];

        switch ($method) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                break;
            case 'PUT':
                $options[CURLOPT_PUT] = true;
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'PATCH':
                $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                break;
            case 'HEAD':
                $options[CURLOPT_NOBODY] = true;
                break;
            case 'GET':
                break;
            default:
                throw new Exception\CurlUnexpectedValueException(
                    "Set method failed: method $method is not supported",
                );
        }

        return $this->setOption($options);
    }

    /**
     * @return \CurlHandle
     * @throws CurlInitException
     */
    private function getConnection(): \CurlHandle
    {
        if (empty($this->connection)) {
            throw new CurlInitException('Curl connection has not be initialized or initialization failed!');
        }

        return $this->connection;
    }
}
