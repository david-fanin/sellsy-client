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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Teknoo\Sellsy\Transport\TransportInterface;

/**
 * Class AbstractTransportTest.
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/sellsy-client Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
abstract class AbstractTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return TransportInterface
     */
    abstract public function buildTransport(): TransportInterface;

    public function testCreateUri()
    {
        self::assertInstanceOf(UriInterface::class, $this->buildTransport()->createUri());
    }

    public function testCreateRequest()
    {
        self::assertInstanceOf(
            RequestInterface::class,
            $this->buildTransport()->createRequest('POST', $this->createMock(UriInterface::class))
        );
    }

    /**
     * @expectedException \TypeError
     */
    public function testCreateStreamWithBadRequest()
    {
        $object = new \stdClass();
        $this->buildTransport()->createStream($object);
    }

    public function testCreateStream()
    {
        $request = [
            ['name' => 'foo', 'contents' => 'bar']
        ];

        self::assertInstanceOf(StreamInterface::class, $this->buildTransport()->createStream($request));
    }

    public function testExecute()
    {
        self::assertInstanceOf(
            ResponseInterface::class,
            $this->buildTransport()->execute($this->createMock(RequestInterface::class))
        );
    }
}
