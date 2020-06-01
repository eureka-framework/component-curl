<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl\Exception;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class CurlException
 *
 * @author Romain Cottard
 */
class HttpClientException extends \Exception implements ClientExceptionInterface
{
}
