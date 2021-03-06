<?php

/**
 * Sellsy Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2016 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/sellsy-client Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\Sellsy\Transport;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Teknoo\Sellsy\Transport\Guzzle;
use Teknoo\Sellsy\Transport\TransportInterface;

/**
 * Class GuzzleTest.
 *
 * @covers \Teknoo\Sellsy\Transport\Guzzle
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/sellsy-client Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class GuzzleTest extends AbstractTransportTest
{
    /**
     * @return TransportInterface
     */
    public function buildTransport(): TransportInterface
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle->expects(self::any())
            ->method('send')
            ->with($this->callback(function ($arg) {
                return $arg instanceof RequestInterface;
            }))
            ->willReturn($this->createMock(ResponseInterface::class));

        return new Guzzle($guzzle);
    }
}
