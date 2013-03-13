<?php

/**
 * This file is part of the Fetch library.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fetch\Test;


use Fetch\Server;

/**
 * Class MessageTest
 *
 * @package Fetch\Test
 * @authod Andrey Kolchenko <andrey@kolchenko.me>
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $server = new Server('imap.example.com');
        $this->assertInstanceOf('\\Fetch\\Imap', $server->getImap(), 'Default Imap wrapper must be set in constructor');
        $this->assertEquals('imap.example.com', $server->getServerPath());
        $this->assertContains('novalidate-cert', $server->getFlags());
    }
}
