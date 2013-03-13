<?php

/**
 * This file is part of the Fetch package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fetch;

/**
 * This library is a wrapper around the Imap library functions included in php. This class in particular manages a
 * connection to the server (imap, pop, etc) and allows for the easy retrieval of stored messages.
 *
 * @package Fetch
 * @author Robert Hafner <tedivm@tedivm.com>
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Server
{
    /**
     * When SSL isn't compiled into PHP we need to make some adjustments to prevent soul crushing annoyances.
     *
     * @var bool
     */
    public $ssl_enable = true;
    /**
     * These are the flags that depend on ssl support being compiled into imap.
     *
     * @var array
     */
    public $ssl_flags = array('ssl', 'validate-cert', 'novalidate-cert', 'tls', 'notls');
    /**
     * This is used to prevent the class from putting up conflicting tags. Both directions- key to value, value to key-
     * are checked, so if "novalidate-cert" is passed then "validate-cert" is removed, and vice-versa.
     *
     * @var array
     */
    public $exclusive_flags = array('validate-cert' => 'novalidate-cert', 'tls' => 'notls');
    /**
     * This is the domain or server path the class is connecting to.
     *
     * @var string
     */
    protected $server_path;
    /**
     * This is the name of the current mailbox the connection is using.
     *
     * @var string
     */
    protected $mailbox;
    /**
     * This is the username used to connect to the server.
     *
     * @var string
     */
    protected $username;
    /**
     * This is the password used to connect to the server.
     *
     * @var string
     */
    protected $password;
    /**
     * This is an array of flags that modify how the class connects to the server. Examples include "ssl" to enforce a
     * secure connection or "novalidate-cert" to allow for self-signed certificates.
     *
     * @link http://us.php.net/manual/en/function.imap-open.php
     * @var array
     */
    protected $flags = array();
    /**
     * This is the port used to connect to the server
     *
     * @var int
     */
    protected $port;
    /**
     * This is the set of options, represented by a bitmask, to be passed to the server during connection.
     *
     * @var int
     */
    protected $options = 0;
    /**
     * This is the resource connection to the server. It is required by a number of imap based functions to specify how
     * to connect.
     *
     * @var resource
     */
    protected $imap_stream;
    /**
     * This is the name of the service currently being used. Imap is the default, although pop3 and nntp are also
     * options
     *
     * @var string
     */
    protected $service = 'imap';
    /**
     * @var Imap
     */
    protected $imap;

    /**
     * This constructor takes the location and service thats trying to be connected to as its arguments.
     *
     * @param string $server_path
     * @param null|int $port
     * @param null|string $service
     */
    public function __construct($server_path, $port = 143, $service = 'imap')
    {
        $this->server_path = $server_path;

        $this->port = $port;

        switch ($port) {
            case 143:
                $this->setFlag('novalidate-cert');
                break;

            case 993:
                $this->setFlag('ssl');
                break;
        }

        $this->service = $service;
        $this->imap = new Imap();
    }

    /**
     * This function sets or removes flag specifying connection behavior. In many cases the flag is just a one word
     * deal, so the value attribute is not required. However, if the value parameter is passed false it will clear that
     * flag.
     *
     * @param string $flag
     * @param null|string|bool $value
     */
    public function setFlag($flag, $value = null)
    {
        if (!$this->ssl_enable && in_array($flag, $this->ssl_flags)) {
            return;
        }

        if (isset($this->exclusive_flags[$flag])) {
            $kill = $flag;
        } elseif ($index = array_search($flag, $this->exclusive_flags)) {
            $kill = $index;
        }

        if (isset($kill) && isset($this->flags[$kill])) {
            unset($this->flags[$kill]);
        }

        if (isset($value) && $value !== true) {
            if ($value == false) {
                unset($this->flags[$flag]);
            } else {
                $this->flags[] = $flag . '=' . $value;
            }
        } else {
            $this->flags[] = $flag;
        }
    }

    /**
     * Get flags.
     *
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Get current IMAP wrapper.
     *
     * @return \Fetch\Imap
     */
    public function getImap()
    {
        return $this->imap;
    }

    /**
     * Set imap wrapper.
     * Very useful for testing.
     *
     * @param \Fetch\Imap $imap
     */
    public function setImap(Imap $imap)
    {
        if (!empty($this->imap_stream)) {
            $this->imap->close($this->imap_stream);
            $this->imap_stream = null;
        }
        $this->imap = $imap;
    }

    /**
     * Returns the current username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the set server path.
     *
     * @return string
     */
    public function getServerPath()
    {
        return $this->server_path;
    }

    /**
     * This function sets the username and password used to connect to the server.
     *
     * @param string $username
     * @param string $password
     */
    public function setAuthentication($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * This function sets the mailbox to connect to.
     *
     * @param string $mailbox
     */
    public function setMailBox($mailbox = '')
    {
        $this->mailbox = $mailbox;
        if (isset($this->imap_stream)) {
            $this->setImapStream();
        }
    }

    /**
     * Get current mailbox.
     *
     * @return string
     */
    public function getMailBox()
    {
        return $this->mailbox;
    }

    /**
     * This funtion is used to set various options for connecting to the server.
     *
     * @param int $bitmask
     *
     * @throws \Exception If bitmask is not a numeric
     */
    public function setOptions($bitmask = 0)
    {
        if (!is_numeric($bitmask)) {
            throw new \Exception('The mask must be a numeric.');
        }

        $this->options = $bitmask;
    }

    /**
     * This function returns the recently received emails as an array of ImapMessage objects.
     *
     * @param null|int $limit
     *
     * @return array An array of ImapMessage objects for emails that were recently received by the server.
     */
    public function getRecentMessages($limit = null)
    {
        return $this->search('Recent', $limit);
    }

    /**
     * This function returns an array of ImapMessage object for emails that fit the criteria passed. The criteria string
     * should be formatted according to the imap search standard, which can be found on the php "imap_search" page or in
     * section 6.4.4 of RFC 2060
     *
     * @link http://us.php.net/imap_search
     * @link http://www.faqs.org/rfcs/rfc2060
     *
     * @param string $criteria
     * @param null|int $limit
     *
     * @return array An array of ImapMessage objects
     */
    public function search($criteria = 'ALL', $limit = null)
    {
        $results = $this->imap->search($this->getImapStream(), $criteria, SE_UID, 'UTF-8');
        if (!empty($results)) {
            $limit = intval($limit);
            if ($limit > 0 && count($results) > $limit) {
                $results = array_slice($results, 0, $limit);
            }

            $messages = array();

            foreach ($results as $message_id) {
                $messages[] = new Message($message_id, $this);
            }

            $results = $messages;
        }

        return $results;
    }

    /**
     * Returns the emails in the current mailbox as an array of ImapMessage objects.
     *
     * @param null|int $limit
     *
     * @return Message[]
     */
    public function getMessages($limit = null)
    {
        $num_messages = $this->numMessages();

        if (isset($limit) && is_numeric($limit) && $limit < $num_messages) {
            $num_messages = $limit;
        }

        if ($num_messages < 1) {
            return array();
        }

        $stream = $this->getImapStream();
        $messages = array();
        for ($i = 1; $i <= $num_messages; $i++) {
            $uid = $this->imap->uid($stream, $i);
            $messages[] = new Message($uid, $this);
        }

        return $messages;
    }

    /**
     * This returns the number of messages that the current mailbox contains.
     *
     * @return int
     */
    public function numMessages()
    {
        return $this->imap->numMsg($this->getImapStream());
    }

    /**
     * This function removes all of the messages flagged for deletion from the mailbox.
     */
    public function expunge()
    {
        $this->imap->expunge($this->getImapStream());
    }

    /**
     * Checks if the given mailbox exists.
     *
     * @param string $mailbox
     *
     * @return bool
     */
    public function hasMailBox($mailbox)
    {
        return (boolean)$this->imap->getMailboxes(
            $this->getImapStream(),
            $this->getServerString(),
            $this->getServerSpecification() . $mailbox
        );
    }

    /**
     * This function gets the current saved imap resource and returns it.
     *
     * @return resource
     */
    public function getImapStream()
    {
        if (!isset($this->imap_stream)) {
            $this->setImapStream();
        }

        return $this->imap_stream;
    }

    /**
     * This function creates or reopens an imap_stream when called.
     */
    protected function setImapStream()
    {
        if (isset($this->imap_stream)) {
            $this->imap->reopen($this->imap_stream, $this->getServerString(), $this->options, 1);
        } else {
            $this->imap_stream = $this->imap->open(
                $this->getServerString(),
                $this->username,
                $this->password,
                $this->options,
                1
            );
        }
    }

    /**
     * This function takes in all of the connection date (server, port, service, flags, mailbox) and creates the string
     * thats passed to the imap_open function.
     *
     * @return string
     */
    public function getServerString()
    {
        $mailbox_path = $this->getServerSpecification();

        if (isset($this->mailbox)) {
            $mailbox_path .= $this->mailbox;
        }

        return $mailbox_path;
    }

    /**
     * Returns the server specification, without adding any mailbox.
     *
     * @return string
     */
    protected function getServerSpecification()
    {
        $mailbox_path = '{' . $this->server_path;

        if (isset($this->port)) {
            $mailbox_path .= ':' . $this->port;
        }

        if ($this->service != 'imap') {
            $mailbox_path .= '/' . $this->service;
        }

        foreach ($this->flags as $flag) {
            $mailbox_path .= '/' . $flag;
        }

        $mailbox_path .= '}';

        return $mailbox_path;
    }

    /**
     * Creates the given mailbox.
     *
     * @param string $mailbox
     */
    public function createMailBox($mailbox)
    {
        $this->imap->createMailbox($this->getImapStream(), $this->getServerSpecification() . $mailbox);
    }
}
