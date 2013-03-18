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
 * Class ServerTest
 *
 * @package Fetch\Test
 * @authod Andrey Kolchenko <andrey@kolchenko.me>
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $server = new Server('imap.example.com');
        $this->assertInstanceOf('\\Fetch\\Imap', $server->getImap(), 'Default Imap wrapper must be set in constructor');
        $this->assertEquals('imap.example.com', $server->getServerPath());
        $this->assertContains('novalidate-cert', $server->getFlags());
    }

    public function dpGetServerString()
    {
        return array(
            array('imap.example.com', 143, 'imap', '', '{imap.example.com:143/novalidate-cert}'),
            array(
                'imap.example.com',
                143,
                'imap',
                'mailbox',
                '{imap.example.com:143/novalidate-cert}mailbox',
            ),
            array('imap.example.com', 993, 'imap', '', '{imap.example.com:993/ssl}'),
            array('imap.example.com', 993, 'imap', 'mailbox', '{imap.example.com:993/ssl}mailbox'),
            array('imap.example.com', 143, 'pop3', '', '{imap.example.com:143/pop3/novalidate-cert}'),
            array(
                'imap.example.com',
                143,
                'pop3',
                'mailbox',
                '{imap.example.com:143/pop3/novalidate-cert}mailbox',
            ),
            array('imap.example.com', 993, 'pop3', '', '{imap.example.com:993/pop3/ssl}'),
            array('imap.example.com', 993, 'pop3', 'mailbox', '{imap.example.com:993/pop3/ssl}mailbox'),
        );
    }

    /**
     * @dataProvider dpGetServerString
     *
     * @param string $server_address
     * @param int $server_port
     * @param string $service
     * @param string $mailbox
     * @param string $expected
     */
    public function testGetServerString($server_address, $server_port, $service, $mailbox, $expected)
    {
        $server = new Server($server_address, $server_port, $service);
        $server->setMailBox($mailbox);
        $this->assertEquals($expected, $server->getServerString());
    }

    /**
     * Test opening connection.
     */
    public function testGetImapStream()
    {
        $imap = $this->getMock('Fetch\\Imap', array('open'));
        $imap->expects($this->once())->method('open')->with(
            $this->equalTo('{imap.example.com:143/novalidate-cert}'),
            $this->equalTo('username'),
            $this->equalTo('password'),
            $this->equalTo(0),
            $this->equalTo(1),
            $this->equalTo(array())
        )->will($this->returnValue('SUCCESS'));

        $server = new Server('imap.example.com');
        $server->setImap($imap);
        $server->setAuthentication('username', 'password');
        $this->assertEquals('SUCCESS', $server->getImapStream());
    }

    /**
     * Test mailbox creation.
     */
    public function testCreateMailBox()
    {
        $imap = $this->getMock('Fetch\\Imap', array('open', 'createMailbox'));
        $imap->expects($this->once())->method('createMailbox')->with(
            $this->anything(),
            $this->equalTo('{imap.example.com:143/novalidate-cert}mailbox')
        );

        $server = new Server('imap.example.com');
        $server->setImap($imap);
        $server->createMailBox('mailbox');
    }
}
