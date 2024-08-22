<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Curl\Tests\Unit;

use Eureka\Component\Curl\Curl;
use PHPUnit\Framework\TestCase;

/**
 * Class CurlTest
 *
 * @author Romain Cottard
 */
class CurlTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanInstantiateHttpClient()
    {
        $curl = new Curl();

        $this->assertInstanceOf(Curl::class, $curl);
    }
}
