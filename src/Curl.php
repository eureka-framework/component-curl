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
    /** @var resource $connection Curl connection resource */
    protected $connection = null;

    /** @var string $message Message data */
    protected $message = '';

    /** @var string $curlInfo Curl info data */
    protected $curlInfo = '';

    /** @var array $defaultOptions Default options used */
    protected $defaultOptions = [];

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
     * @param null|string $url Url for connection.
     * @return $this
     * @throws CurlInitException
     */
    public function init(string $url = null): self
    {
        if (empty($url)) {
            $this->connection = curl_init();
        } else {
            $this->connection = curl_init($url);
        }

        if ($this->connection === false) {
            throw new CurlInitException(__METHOD__ . '|Initialization failed !');
        }

        return $this;
    }

    /**
     * Close connection.
     *
     * @return $this
     */
    public function close(): self
    {
        if (!empty($this->connection)) {
            curl_close($this->connection);
        }

        return $this;
    }

    /**
     * Execute cURL command.
     *
     * @return bool|string
     */
    public function exec()
    {
        $result         = curl_exec($this->connection);
        $this->curlInfo = curl_getinfo($this->connection);

        return $result;
    }

    /**
     * Get last cURL error message.
     *
     * @return string Error message.
     */
    public function getError(): string
    {
        if (null !== $this->message) {
            $message       = $this->message;
            $this->message = null;

            return $message;
        }

        return curl_error($this->connection);
    }

    /**
     * Get last cURL error number.
     *
     * @return int Error number.
     */
    public function getErrorNumber()
    {
        return curl_errno($this->connection);
    }

    /**
     * Return curl infos stored of exist, else wrap curl_getinfo()
     *
     * @return array Array of data about previous curl request.
     */
    public function getInfo(): array
    {
        if (is_array($this->curlInfo)) {
            return $this->curlInfo;
        }

        return curl_getinfo($this->connection);
    }

    /**
     * Check if previous request is success or not
     * (success: no error & http code is 2XX or 3XX)
     *
     * @return bool
     */
    public function isSuccess()
    {
        $info = $this->getInfo();

        switch (true) {
            case false === $info:
                $this->message = 'Cannot get information from connection!';
                break;
            case !is_array($info):
                $this->message = 'Curl information is not an array!';
                break;
            case !isset($info['http_code']):
                $this->message = 'No information about "http_code"!';
                break;
            case substr($info['http_code'], 0, 1) != '2' && substr($info['http_code'], 0, 1) != '3':
                $this->message = '"http_code" is not a 2XX or 3XX status code! http-code: ' . $info['http_code'];
                break;
            // 0 or -1 : No size info
            case (int) $info['download_content_length'] > 0 && (int) $info['download_content_length'] !== (int) $info['size_download']:
                $this->message = 'Transfer did not complete!';
                break;
            default:
                $this->message = null;
                break;
        }

        return (empty($this->message));
    }

    /**
     * Override default option (array or name/value)
     *
     * @param array|string $name Name or array of options to set.
     * @param null|mixed $value Value to set.
     * @return $this
     */
    public function setOptionDefault($name, $value = null)
    {
        if (is_array($name) && null === $value) {
            $this->defaultOptions = $name + $this->defaultOptions;
        } else {
            $this->defaultOptions[$name] = $value;
        }

        return $this;
    }

    /**
     * Set option (array or name/value)
     *
     * @param array|string $name Name or array of options to set.
     * @param null|mixed $value Value to set.
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     */
    public function setOption($name, $value = null)
    {
        if (!is_resource($this->connection)) {
            $this->init();
        }

        $openBasedir = ini_get('openBasedir');

        if (is_array($name) && !empty($name) && null === $value) {
            if (isset($name[CURLOPT_FOLLOWLOCATION]) && (!empty($openBasedir))) {
                $name[CURLOPT_FOLLOWLOCATION] = false;
            }

            if (false === curl_setopt_array($this->connection, $name)) {
                $error = $this->getError();
                $errno = $this->getErrorNumber();
                $this->close();
                throw new Exception\CurlOptionException(__METHOD__ . '|Set option array failed ! (error: ' . $error . ')', $errno);
            }
        } else {
            if ($name == CURLOPT_FOLLOWLOCATION && (!empty($openBasedir))) {
                $value = false;
            }

            if (false === curl_setopt($this->connection, $name, $value)) {
                $error = $this->getError();
                $errno = $this->getErrorNumber();
                $this->close();
                throw new Exception\CurlOptionException(__METHOD__ . '|Set option failed ! (error: ' . $error . ')', $errno);
            }
        }

        return $this;
    }

    /**
     * Sets the POST data
     *
     * @param array|string $data An array of key => value pairs, or an urlencoded string
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     */
    public function setPostData($data)
    {
        $this->setOption(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * Sets the URL for the request
     *
     * @param string $url The URL
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     */
    public function setUrl($url)
    {
        $this->setOption(CURLOPT_URL, (string) $url);

        return $this;
    }

    /**
     * Configures the return behavior.
     *
     * @param bool $return If true, exec() should return the response content.
     * Otherwise, the response content will be sent to the output stream.
     * @return $this
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     */
    public function setReturn($return)
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, (bool) $return);

        return $this;
    }

    /**
     * Sets the request HTTP method
     *
     * @param string $method The method name (GET, POST, PUT or DELETE)
     * @return $this
     * @throws Exception\CurlUnexpectedValueException
     * @throws Exception\CurlOptionException
     * @throws Exception\CurlInitException
     */
    public function setMethod($method)
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
            case 'HEAD':
                $options[CURLOPT_NOBODY] = true;
                break;
            case 'GET':
                break;
            default:
                throw new Exception\CurlUnexpectedValueException("Set method failed: method ${method} is not supported");
        }

        $this->setOption($options);

        return $this;
    }
}
