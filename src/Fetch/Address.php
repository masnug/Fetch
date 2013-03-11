<?php
/**
 * This file is a part of project.
 *
 * @author Andrey Kolchenko <komexx@gmail.com>
 */

namespace Fetch;

/**
 * Class Address
 *
 * @package Fetch
 */
class Address
{
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $raw_name;

    /**
     * @param string $email Email address
     *
     * @throws \InvalidArgumentException If email address is invalid
     */
    function __construct($email)
    {
        /**
         * An easy validator. =)
         */
        if (!preg_match('/^[^@]+@.+$/i', $email)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
        $this->email = $email;
    }

    /**
     * Get current email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get email owner name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email owner name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        if (is_string($name)) {
            $this->raw_name = $name;
            $name = imap_mime_header_decode($name);
            if (empty($name[0])) {
                $this->name = '';
            } else {
                $this->name = $name[0]->text;
            }
        }
    }

    /**
     * Get owner name as is (without decoding).
     *
     * @return string
     */
    public function getRawName()
    {
        return $this->raw_name;
    }

}