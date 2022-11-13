<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl\Tests;

use Eureka\Component\Curl\HttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * Class HttpClient
 *
 * @author Romain Cottard
 */
class HttpClientTest extends TestCase
{
    /**
     * @return void
     */
    public function testICanInstantiateHttpClient()
    {
        $client = new HttpClient(1, 1);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }
}
