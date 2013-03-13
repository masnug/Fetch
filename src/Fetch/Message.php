<?php

/**
 * This file is part of the Fetch package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Fetch;

/**
 * This library is a wrapper around the Imap library functions included in php.
 * This class represents a single email message as retrieved from the Imap.
 *
 * @package Fetch
 * @author Robert Hafner <tedivm@tedivm.com>
 * @authot Andrey Kolchenko <andrey@kolchenko.me>
 */
class Message
{
    /**
     * This value defines the encoding we want the email message to use.
     *
     * @var string
     */
    protected $charset = 'UTF-8//TRANSLIT';
    /**
     * This is an array of the various imap flags that can be set.
     *
     * @var string
     */
    protected $flag_types = array('recent', 'flagged', 'answered', 'deleted', 'seen', 'draft');
    /**
     * This is the connection/mailbox class that the email came from.
     *
     * @var Server
     */
    protected $imap_connection;
    /**
     * This is the unique identifier for the message. This corresponds to the imap "uid", which we use instead of the
     * sequence number.
     *
     * @var int
     */
    protected $uid;
    /**
     * This is a reference to the Imap stream generated by 'imap_open'.
     *
     * @var resource
     */
    protected $imap_stream;
    /**
     * This as an object which contains header information for the message.
     *
     * @var array
     */
    protected $headers;
    /**
     * This is an object which contains various status messages and other information about the message.
     *
     * @var \stdClass
     */
    protected $message_overview;
    /**
     * This is an object which contains information about the structure of the message body.
     *
     * @var \stdClass
     */
    protected $structure;
    /**
     * This is an array with the index being imap flags and the value being a boolean specifying whether that flag is
     * set or not.
     *
     * @var array
     */
    protected $status = array();
    /**
     * This holds the plantext email message.
     *
     * @var string
     */
    protected $plaintext_message;
    /**
     * This holds the html version of the email.
     *
     * @var string
     */
    protected $html_message;
    /**
     * This is the date the email was sent.
     *
     * @var \DateTime
     */
    protected $date;
    /**
     * This is the subject of the email.
     *
     * @var string
     */
    protected $subject;
    /**
     * This is the size of the email.
     *
     * @var int
     */
    protected $size;
    /**
     * This is an array containing information about the address the email came from.
     *
     * @var string
     */
    protected $from;
    /**
     * This is an array of arrays that contain information about the addresses the email was cc'd to.
     *
     * @var array
     */
    protected $to;
    /**
     * This is an array of arrays that contain information about the addresses the email was cc'd to.
     *
     * @var array
     */
    protected $cc;
    /**
     * This is an array of arrays that contain information about the addresses that should receive replies to the email.
     *
     * @var array
     */
    protected $reply_to;
    /**
     * This is an array of ImapAttachments retrieved from the message.
     *
     * @var Attachment[]
     */
    protected $attachments = array();

    /**
     * This constructor takes in the uid for the message and the Imap class representing the mailbox the message should be opened from.
     * This constructor should generally not be called directly, but rather retrieved through the apprioriate Imap functions.
     *
     * @param int $message_unique_id
     * @param Server $mailbox
     */
    public function __construct($message_unique_id, Server $mailbox)
    {
        $this->imap_connection = $mailbox;
        $this->uid = $message_unique_id;
        $this->imap_stream = $this->imap_connection->getImapStream();
        $this->loadMessage();
    }

    /**
     * This function is called when the message class is loaded.
     * It loads general information about the message from the imap server.
     *
     */
    protected function loadMessage()
    {
        /** First load the message overview information */

        $message_overview = $this->getOverview();

        $this->subject = $message_overview->subject;
        $this->date = new \DateTime($message_overview->date);
        $this->size = $message_overview->size;

        foreach ($this->flag_types as $flag) {
            $this->status[$flag] = ($message_overview->$flag == 1);
        }

        /** Next load in all of the header information */

        $headers = $this->getHeaders();

        if (isset($headers['to'])) {
            $this->to = $this->processAddressObject($headers['to']);
        }

        if (isset($headers['cc'])) {
            $this->cc = $this->processAddressObject($headers['cc']);
        }

        $this->from = $this->processAddressObject($headers['from']);
        $this->reply_to = isset($headers['reply_to']) ? $this->processAddressObject($headers['reply_to']) : $this->from;

        /** Finally load the structure itself */

        $structure = $this->getStructure();

        if (!isset($structure->parts)) {
            // not multipart
            $this->processStructure($structure);
        } else {
            // multipart
            foreach ($structure->parts as $id => $part) {
                $this->processStructure($part, $id + 1);
            }
        }
    }

    /**
     * This function returns an object containing information about the message. This output is similar to that over the
     * imap_fetch_overview function, only instead of an array of message overviews only a single result is returned. The
     * results are only retrieved from the server once unless passed true as a parameter.
     *
     * @param bool $force_reload
     *
     * @return \stdClass
     */
    public function getOverview($force_reload = false)
    {
        if ($force_reload || empty($this->message_overview)) {
            // returns an array, and since we just want one message we can grab the only result
            $results = imap_fetch_overview($this->imap_stream, $this->uid, FT_UID);
            $this->message_overview = array_shift($results);
        }

        return $this->message_overview;
    }

    /**
     * This function returns an object containing the headers of the message. This is done by taking the raw headers
     * and running them through the imap_rfc822_parse_headers function. The results are only retrieved from the server
     * once unless passed true as a parameter.
     *
     * @param bool $force_reload
     *
     * @return array
     */
    public function getHeaders($force_reload = false)
    {
        if ($force_reload || empty($this->headers)) {
            // raw headers (since imap_headerinfo doesn't use the unique id)
            $raw_headers = imap_fetchheader($this->imap_stream, $this->uid, FT_UID);
            $decoded_headers = iconv_mime_decode_headers($raw_headers, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            $this->headers = array_change_key_case($decoded_headers, CASE_LOWER);
        }

        return $this->headers;
    }

    /**
     * This function takes in an array of the address objects generated by the message headers and turns them into an
     * associative array.
     *
     * @param string $addresses
     *
     * @return Address[]
     */
    protected function processAddressObject($addresses)
    {
        $output_addresses = array();
        $decoded_addresses = imap_rfc822_parse_adrlist($addresses, null);
        if (is_array($decoded_addresses)) {
            foreach ($decoded_addresses as $address) {
                $current_address = new Address($address->mailbox . '@' . $address->host);
                if (isset($address->personal)) {
                    $current_address->setName($address->personal);
                }
                array_push($output_addresses, $current_address);
            }
        }

        return $output_addresses;
    }

    /**
     * This function returns an object containing the structure of the message body. This is the same object thats
     * returned by imap_fetchstructure. The results are only retrieved from the server once unless passed true as a
     * parameter.
     *
     * @param bool $force_reload
     *
     * @return \stdClass
     */
    public function getStructure($force_reload = false)
    {
        if ($force_reload || !isset($this->structure)) {
            $this->structure = imap_fetchstructure($this->imap_stream, $this->uid, FT_UID);
        }

        return $this->structure;
    }

    /**
     * This function takes in a structure and identifier and processes that part of the message. If that portion of the
     * message has its own subparts, those are recursively processed using this function.
     *
     * @param \stdClass $structure
     * @param string $part_identifier
     *
     * @todoa process attachments.
     */
    protected function processStructure($structure, $part_identifier = null)
    {
        $parameters = self::getParametersFromStructure($structure);

        if (isset($parameters['name']) || isset($parameters['filename'])) {
            $attachment = new Attachment($this, $structure, $part_identifier);
            $this->attachments[] = $attachment;
        } elseif ($structure->type == 0 || $structure->type == 1) {

            $message_body = isset($part_identifier) ?
                imap_fetchbody($this->imap_stream, $this->uid, $part_identifier, FT_UID)
                : imap_body($this->imap_stream, $this->uid, FT_UID);

            $message_body = self::decode($message_body, $structure->encoding);

            if (!empty($parameters['charset']) && $parameters['charset'] !== $this->charset) {
                $message_body = iconv($parameters['charset'], $this->charset, $message_body);
            }

            if (strtolower($structure->subtype) == 'plain' || $structure->type == 1) {
                if (isset($this->plaintext_message)) {
                    $this->plaintext_message .= PHP_EOL . PHP_EOL;
                } else {
                    $this->plaintext_message = '';
                }

                $this->plaintext_message .= trim($message_body);
            } else {

                if (isset($this->html_message)) {
                    $this->html_message .= '<br><br>';
                } else {
                    $this->html_message = '';
                }

                $this->html_message .= $message_body;
            }
        }

        if (isset($structure->parts)) { // multipart: iterate through each part

            foreach ($structure->parts as $partIndex => $part) {
                $partId = $partIndex + 1;

                if (isset($part_identifier)) {
                    $partId = $part_identifier . '.' . $partId;
                }

                $this->processStructure($part, $partId);
            }
        }
    }

    /**
     * Takes in a section structure and returns its parameters as an associative array.
     *
     * @param \stdClass $structure
     *
     * @return array
     */
    public static function getParametersFromStructure($structure)
    {
        $parameters = array();
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $parameter) {
                $parameters[strtolower($parameter->attribute)] = $parameter->value;
            }
        }

        if (isset($structure->dparameters)) {
            foreach ($structure->dparameters as $parameter) {
                $parameters[strtolower($parameter->attribute)] = $parameter->value;
            }
        }

        return $parameters;
    }

    /**
     * This function returns the body type that an imap integer maps to.
     *
     * @param int $id
     *
     * @return string
     */
    public static function typeIdToString($id)
    {
        switch ($id) {
            case 0:
                return 'text';

            case 1:
                return 'multipart';

            case 2:
                return 'message';

            case 3:
                return 'application';

            case 4:
                return 'audio';

            case 5:
                return 'image';

            case 6:
                return 'video';

            default:
            case 7:
                return 'other';
        }
    }

    /**
     * This function returns the message body of the email. By default it returns the plaintext version. If a plaintext
     * version is requested but not present, the html version is stripped of tags and returned. If the opposite occurs,
     * the plaintext version is given some html formatting and returned. If neither are present the return value will be
     * false.
     *
     * @param bool $html Pass true to receive an html response.
     *
     * @return string|bool Returns false if no body is present.
     */
    public function getMessageBody($html = false)
    {
        if ($html) {
            if (!isset($this->html_message) && isset($this->plaintext_message)) {
                $output = nl2br($this->plaintext_message);

                return $output;

            } elseif (isset($this->html_message)) {
                return $this->html_message;
            }
        } else {
            if (!isset($this->plaintext_message) && isset($this->html_message)) {
                $output = strip_tags($this->html_message);

                return $output;
            } elseif (isset($this->plaintext_message)) {
                return $this->plaintext_message;
            }
        }

        return false;
    }

    /**
     * This function returns either an Address object or, optionally, a string that can be used in mail headers.
     *
     * @param string $type Should be 'to', 'cc', 'from', or 'reply_to'.
     * @param bool $as_string
     *
     * @return array|string
     */
    public function getAddresses($type, $as_string = false)
    {
        $address_types = array('to', 'cc', 'from', 'reply_to');

        if (!in_array($type, $address_types) || !isset($this->$type) || count($this->$type) < 1) {
            return array();
        }

        $addresses = $this->$type;
        if ($as_string) {
            $addresses = join(',', $addresses);
        }

        return $addresses;
    }

    /**
     * This function returns the date, as a timestamp, of when the email was sent.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return empty($this->date) ? false : $this->date;
    }

    /**
     * This returns the subject of the message.
     *
     * @return string
     */
    public function getRawSubject()
    {
        return $this->subject;
    }

    /**
     * Return decoded subject of the message.
     *
     * @return string
     */
    public function getSubject()
    {
        if (empty($this->subject)) {
            return '';
        } else {
            return $this->decode($this->subject, 'mime-header');
        }
    }

    /**
     * This function takes in the message data and encoding type and returns the decoded data.
     *
     * @param string $data
     * @param int|string $encoding
     *
     * @return string
     */
    public static function decode($data, $encoding)
    {
        if (!is_numeric($encoding)) {
            $encoding = strtolower($encoding);
        }

        switch ($encoding) {
            case 'quoted-printable':
            case 4:
                return quoted_printable_decode($data);

            case 'base64':
            case 3:
                return base64_decode($data);
            case 'mime-header':
                $decoded = imap_mime_header_decode($data);
                if (!empty($decoded[0])) {
                    if ($decoded[0]->charset == 'default') {
                        $data = $decoded[0]->text;
                    } else {
                        $data = iconv($decoded[0]->charset, 'UTF-8', $decoded[0]->text);
                    }
                }

                return $data;
            default:
                return $data;
        }
    }

    /**
     * This function marks a message for deletion. It is important to note that the message will not be deleted form the
     * mailbox until the Imap->expunge it run.
     *
     * @return bool
     */
    public function delete()
    {
        return imap_delete($this->imap_stream, $this->uid, FT_UID);
    }

    /**
     * This function returns Imap this message came from.
     *
     * @return Server
     */
    public function getImapBox()
    {
        return $this->imap_connection;
    }

    /**
     * This function returns the unique id that identifies the message on the server.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * This function returns the attachments a message contains. If a filename is passed then just that ImapAttachment
     * is returned, unless
     *
     * @param null|string $filename
     *
     * @return array|bool|Attachment[]
     */
    public function getAttachments($filename = null)
    {
        if (!isset($this->attachments) || count($this->attachments) < 1) {
            return false;
        }

        if (!isset($filename)) {
            return $this->attachments;
        }

        $results = array();
        foreach ($this->attachments as $attachment) {
            if ($attachment->getFileName() == $filename) {
                $results[] = $attachment;
            }
        }

        switch (count($results)) {
            case 0:
                return false;

            case 1:
                return array_shift($results);

            default:
                return $results;
                break;
        }
    }

    /**
     * This function checks to see if an imap flag is set on the email message.
     *
     * @param string $flag Recent, Flagged, Answered, Deleted, Seen, Draft
     *
     * @return bool
     */
    public function checkFlag($flag = 'flagged')
    {
        return (isset($this->status[$flag]) && $this->status[$flag] == true);
    }

    /**
     * This function is used to enable or disable a flag on the imap message.
     *
     * @param string $flag Flagged, Answered, Deleted, Seen, Draft
     * @param bool $enable
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function setFlag($flag, $enable = true)
    {
        if (!in_array($flag, $this->flag_types) || $flag == 'recent') {
            throw new \InvalidArgumentException('Unable to set invalid flag "' . $flag . '"');
        }

        $flag = '\\' . ucfirst($flag);

        if ($enable) {
            return imap_setflag_full($this->imap_stream, $this->uid, $flag, ST_UID);
        } else {
            return imap_clearflag_full($this->imap_stream, $this->uid, $flag, ST_UID);
        }
    }

    /**
     * This function is used to move a mail to the given mailbox.
     *
     * @param $mailbox
     *
     * @return bool
     */
    public function moveToMailBox($mailbox)
    {
        return imap_mail_copy($this->imap_stream, $this->uid, $mailbox, CP_UID | CP_MOVE);
    }

    /**
     * Get carbon copy.
     * A list of more recipients that will be seen by all other recipients.
     *
     * @return Address[]
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * The senders email address.
     *
     * @return Address[]
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * The email address where replies should be sent to.
     *
     * @return Address[]
     */
    public function getReplyTo()
    {
        return $this->reply_to;
    }

    /**
     * A list of recipient emails.
     *
     * @return Address[]
     */
    public function getTo()
    {
        return $this->to;
    }
}