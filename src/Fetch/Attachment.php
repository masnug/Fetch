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
 * This library is a wrapper around the Imap library functions included in php. This class wraps around an attachment
 * in a message, allowing developers to easily save or display attachments.
 *
 * @package Fetch
 * @author Robert Hafner <tedivm@tedivm.com>
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Attachment
{
    /**
     * This is the structure object for the piece of the message body that the attachment is located it.
     *
     * @var \stdClass
     */
    protected $structure;
    /**
     * This is the unique identifier for the message this attachment belongs to.
     *
     * @var int
     */
    protected $message_id;
    /**
     * This is the ImapResource.
     *
     * @var resource
     */
    protected $imap_stream;
    /**
     * This is the id pointing to the section of the message body that contains the attachment.
     *
     * @var int
     */
    protected $part_id;
    /**
     * This is the attachments filename.
     *
     * @var string
     */
    protected $filename;
    /**
     * This is the size of the attachment.
     *
     * @var int
     */
    protected $size;
    /**
     * This stores the data of the attachment so it doesn't have to be retrieved from the server multiple times. It is
     * only populated if the getData() function is called and should not be directly used.
     *
     * @internal
     * @var array
     */
    protected $data;
    /**
     * @var string
     */
    protected $mime_type;
    /**
     * @var Imap
     */
    protected $imap;

    /**
     * This function takes in an ImapMessage, the structure object for the particular piece of the message body that the
     * attachment is located at, and the identifier for that body part. As a general rule you should not be creating
     * instances of this yourself, but rather should get them from an ImapMessage class.
     *
     * @param Message $message
     * @param \stdClass $structure
     * @param string $part_identifier
     */
    public function __construct(Message $message, \stdClass $structure, $part_identifier = null)
    {
        $this->message_id = $message->getUid();
        $this->imap_stream = $message->getImapBox()->getImapStream();
        $this->imap = $message->getImapBox()->getImap();
        $this->structure = $structure;

        if (isset($part_identifier)) {
            $this->part_id = $part_identifier;
        }

        $parameters = Message::getParametersFromStructure($structure);

        if (isset($parameters['filename'])) {
            $this->filename = $parameters['filename'];
        } elseif (isset($parameters['name'])) {
            $this->filename = $parameters['name'];
        }

        $this->size = $structure->bytes;

        $this->mime_type = Message::typeIdToString($structure->type);

        if (isset($structure->subtype)) {
            $this->mime_type .= '/' . strtolower($structure->subtype);
        }

        $this->encoding = $structure->encoding;
    }

    /**
     * This function returns the mimetype of the attachment.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * This returns the size of the attachment.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * This function saves the attachment to the passed directory, keeping the original name of the file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function saveToDirectory($path)
    {
        $path = rtrim($path, '/') . '/';

        if (is_dir($path)) {
            return $this->saveAs($path . $this->getFileName());
        }

        return false;
    }

    /**
     * This function saves the attachment to the exact specified location.
     *
     * @param string $path
     *
     * @return bool
     */
    public function saveAs($path)
    {
        $dirname = dirname($path);
        if (file_exists($path)) {
            if (!is_writable($path)) {
                return false;
            }
        } elseif (!is_dir($dirname) || !is_writable($dirname)) {
            return false;
        }

        if (($file_pointer = fopen($path, 'w')) == false) {
            return false;
        }

        $results = fwrite($file_pointer, $this->getData());
        fclose($file_pointer);

        return is_numeric($results);
    }

    /**
     * This function returns the data of the attachment. Combined with getMimeType() it can be used to directly output
     * data to a browser.
     *
     * @return string
     */
    public function getData()
    {
        if (!isset($this->data)) {
            $message_body = isset($this->part_id) ?
                $this->imap->fetchBody($this->imap_stream, $this->message_id, $this->part_id, FT_UID)
                : $this->imap->body($this->imap_stream, $this->message_id, FT_UID);

            $message_body = Message::decode($message_body, $this->encoding);
            $this->data = $message_body;
        }

        return $this->data;
    }

    /**
     * This returns the filename of the attachment, or false if one isn't given.
     *
     * @return string
     */
    public function getFileName()
    {
        return (isset($this->filename)) ? $this->filename : false;
    }
}
