<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl\Tests\Unit;

use Eureka\Component\Curl\HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
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
        //~ Arrange
        $factory = new Psr17Factory();
        $request = $factory->createRequest('GET', 'https://example.com');

        //~ Act
        $client   = new HttpClient(1, 1);
        $response = $client->sendRequest($request);

        //~ Assert

        $this->assertSame(200, $response->getStatusCode());
    }
}
