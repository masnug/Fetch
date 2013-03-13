<?php
/**
 * This file is a part of Fetch project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Fetch;


/**
 * Wrapper over imap functions.
 *
 * @package Fetch
 */
class Imap
{
    /**
     * Open an IMAP stream to a mailbox.
     *
     * @link http://php.net/manual/en/function.imap-open.php
     *
     * @param string $mailbox <p>
     * A mailbox name consists of a server and a mailbox path on this server.
     * The special name INBOX stands for the current users
     * personal mailbox. Mailbox names that contain international characters
     * besides those in the printable ASCII space have to be encoded width
     * <b>imap_utf7_encode</b>.
     * </p>
     * <p>
     * The server part, which is enclosed in '{' and '}', consists of the servers
     * name or ip address, an optional port (prefixed by ':'), and an optional
     * protocol specification (prefixed by '/').
     * </p>
     * <p>
     * The server part is mandatory in all mailbox
     * parameters.
     * </p>
     * <p>
     * All names which start with { are remote names, and are
     * in the form "{" remote_system_name [":" port] [flags] "}"
     * [mailbox_name] where:
     * remote_system_name - Internet domain name or
     * bracketed IP address of server.
     * @param string $username <p>
     * The user name
     * </p>
     * @param string $password <p>
     * The password associated with the <i>username</i>
     * </p>
     * @param int $options [optional] <p>
     * The <i>options</i> are a bit mask with one or more of
     * the following:
     * <b>OP_READONLY</b> - Open mailbox read-only
     * @param int $n_retries [optional] <p>
     * Number of maximum connect attempts
     * </p>
     * @param array $params [optional] <p>
     * Connection parameters, the following (string) keys maybe used
     * to set one or more connection parameters:
     * DISABLE_AUTHENTICATOR - Disable authentication properties
     *
     * @return resource an IMAP stream
     * @throws \RuntimeException If connections fail
     */
    public function open($mailbox, $username, $password, $options = 0, $n_retries = 0, array $params = null)
    {
        $stream = imap_open($mailbox, $username, $password, $options, $n_retries, $params);
        if ($stream === false) {
            throw new \RuntimeException(imap_last_error());
        }

        return $stream;
    }

    /**
     * This function returns an array of messages matching the given search criteria.
     *
     * @link http://php.net/manual/en/function.imap-search.php
     *
     * @param resource $stream
     * @param string $criteria <p>
     * A string, delimited by spaces, in which the following keywords are
     * allowed. Any multi-word arguments (e.g.
     * FROM "joey smith") must be quoted. Results will match
     * all <i>criteria</i> entries.
     * ALL - return all messages matching the rest of the criteria
     * @param int $options [optional] <p>
     * Valid values for <i>options</i> are
     * <b>SE_UID</b>, which causes the returned array to
     * contain UIDs instead of messages sequence numbers.
     * </p>
     * @param string $charset [optional]
     *
     * @return array an array of message numbers or UIDs.
     */
    public function search($stream, $criteria, $options, $charset = null)
    {
        $result = imap_search($stream, $criteria, $options, $charset);
        if (empty($result)) {
            $result = array();
        }

        return $result;
    }

    /**
     * This function returns the UID for the given message sequence number.
     *
     * @link http://php.net/manual/en/function.imap-uid.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number.
     * </p>
     *
     * @return int The UID of the given message.
     */
    public function uid($stream, $msg_number)
    {
        return imap_uid($stream, $msg_number);
    }

    /**
     * Gets the number of messages in the current mailbox.
     *
     * @link http://php.net/manual/en/function.imap-num-msg.php
     *
     * @param resource $stream
     *
     * @return int Return the number of messages in the current mailbox, as an integer.
     */
    public function numMsg($stream)
    {
        return imap_num_msg($stream);
    }

    /**
     * Delete all messages marked for deletion.
     *
     * @link http://php.net/manual/en/function.imap-expunge.php
     *
     * @param resource $stream
     */
    public function expunge($stream)
    {
        imap_expunge($stream);
    }

    /**
     * Read the list of mailboxes, returning detailed information on each one.
     *
     * @link http://php.net/manual/en/function.imap-getmailboxes.php
     *
     * @param resource $stream
     * @param string $ref <p>
     * <i>ref</i> should normally be just the server
     * specification as described in <b>imap_open</b>
     * </p>
     * @param string $pattern Specifies where in the mailbox hierarchy
     * to start searching.</p>There are two special characters you can
     * pass as part of the <i>pattern</i>:
     * &#x00027;*&#x00027; and &#x00027;&#37;&#x00027;.
     * &#x00027;*&#x00027; means to return all mailboxes. If you pass
     * <i>pattern</i> as &#x00027;*&#x00027;, you will
     * get a list of the entire mailbox hierarchy.
     * &#x00027;&#37;&#x00027;
     * means to return the current level only.
     * &#x00027;&#37;&#x00027; as the <i>pattern</i>
     * parameter will return only the top level
     * mailboxes; &#x00027;~/mail/&#37;&#x00027; on UW_IMAPD will return every mailbox in the ~/mail directory, but none in subfolders of that directory.</p>
     *
     * @return array an array of objects containing mailbox information. Each
     * object has the attributes <i>name</i>, specifying
     * the full name of the mailbox; <i>delimiter</i>,
     * which is the hierarchy delimiter for the part of the hierarchy
     * this mailbox is in; and
     * <i>attributes</i>. <i>Attributes</i>
     * is a bitmask that can be tested against:
     * <p>
     * <b>LATT_NOINFERIORS</b> - This mailbox contains, and may not contain any
     * "children" (there are no mailboxes below this one). Calling
     * <b>imap_createmailbox</b> will not work on this mailbox.
     * </p>
     * <p>
     * <b>LATT_NOSELECT</b> - This is only a container,
     * not a mailbox - you cannot open it.
     * </p>
     * <p>
     * <b>LATT_MARKED</b> - This mailbox is marked. This means that it may
     * contain new messages since the last time it was checked. Not provided by all IMAP
     * servers.
     * </p>
     * <p>
     * <b>LATT_UNMARKED</b> - This mailbox is not marked, does not contain new
     * messages. If either <b>MARKED</b> or <b>UNMARKED</b> is
     * provided, you can assume the IMAP server supports this feature for this mailbox.
     * </p>
     */
    public function getMailboxes($stream, $ref, $pattern)
    {
        return imap_getmailboxes($stream, $ref, $pattern);
    }

    /**
     * Create a new mailbox.
     *
     * @link http://php.net/manual/en/function.imap-createmailbox.php
     *
     * @param resource $stream
     * @param string $mailbox <p>
     * The mailbox name, see <b>imap_open</b> for more
     * information. Names containing international characters should be
     * encoded by <b>imap_utf7_encode</b>
     * </p>
     *
     * @throws \RuntimeException If operation fail
     */
    public function createMailbox($stream, $mailbox)
    {
        $result = imap_createmailbox($stream, $mailbox);
        if (!$result) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Returns header for a message.
     *
     * @link http://php.net/manual/en/function.imap-fetchheader.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number
     * </p>
     * @param int $options [optional] <p>
     * The possible <i>options</i> are:
     * <b>FT_UID</b> - The <i>msgno</i>
     * argument is a UID
     *
     * @return string the header of the specified message as a text string.
     */
    public function fetchHeader($stream, $msg_number, $options = 0)
    {
        return imap_fetchheader($stream, $msg_number, $options);
    }

    /**
     * Read the structure of a particular message.
     *
     * @link http://php.net/manual/en/function.imap-fetchstructure.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number
     * </p>
     * @param int $options [optional] <p>
     * This optional parameter only has a single option,
     * <b>FT_UID</b>, which tells the function to treat the
     * <i>msg_number</i> argument as a
     * UID.
     * </p>
     *
     * @return object an object includes the envelope, internal date, size, flags and
     * body structure along with a similar object for each mime attachment. The
     * structure of the returned objects is as follows:
     * </p>
     * <p>
     * <table>
     * Returned Objects for <b>imap_fetchstructure</b>
     * <tr valign="top">
     * <td>type</td>
     * <td>Primary body type</td>
     * </tr>
     * <tr valign="top">
     * <td>encoding</td>
     * <td>Body transfer encoding</td>
     * </tr>
     * <tr valign="top">
     * <td>ifsubtype</td>
     * <td><b>TRUE</b> if there is a subtype string</td>
     * </tr>
     * <tr valign="top">
     * <td>subtype</td>
     * <td>MIME subtype</td>
     * </tr>
     * <tr valign="top">
     * <td>ifdescription</td>
     * <td><b>TRUE</b> if there is a description string</td>
     * </tr>
     * <tr valign="top">
     * <td>description</td>
     * <td>Content description string</td>
     * </tr>
     * <tr valign="top">
     * <td>ifid</td>
     * <td><b>TRUE</b> if there is an identification string</td>
     * </tr>
     * <tr valign="top">
     * <td>id</td>
     * <td>Identification string</td>
     * </tr>
     * <tr valign="top">
     * <td>lines</td>
     * <td>Number of lines</td>
     * </tr>
     * <tr valign="top">
     * <td>bytes</td>
     * <td>Number of bytes</td>
     * </tr>
     * <tr valign="top">
     * <td>ifdisposition</td>
     * <td><b>TRUE</b> if there is a disposition string</td>
     * </tr>
     * <tr valign="top">
     * <td>disposition</td>
     * <td>Disposition string</td>
     * </tr>
     * <tr valign="top">
     * <td>ifdparameters</td>
     * <td><b>TRUE</b> if the dparameters array exists</td>
     * </tr>
     * <tr valign="top">
     * <td>dparameters</td>
     * <td>An array of objects where each object has an
     * "attribute" and a "value"
     * property corresponding to the parameters on the
     * Content-disposition MIME
     * header.</td>
     * </tr>
     * <tr valign="top">
     * <td>ifparameters</td>
     * <td><b>TRUE</b> if the parameters array exists</td>
     * </tr>
     * <tr valign="top">
     * <td>parameters</td>
     * <td>An array of objects where each object has an
     * "attribute" and a "value"
     * property.</td>
     * </tr>
     * <tr valign="top">
     * <td>parts</td>
     * <td>An array of objects identical in structure to the top-level
     * object, each of which corresponds to a MIME body
     * part.</td>
     * </tr>
     * </table>
     * </p>
     * <p>
     * <table>
     * Primary body type (may vary with used library)
     * <tr valign="top"><td>0</td><td>text</td></tr>
     * <tr valign="top"><td>1</td><td>multipart</td></tr>
     * <tr valign="top"><td>2</td><td>message</td></tr>
     * <tr valign="top"><td>3</td><td>application</td></tr>
     * <tr valign="top"><td>4</td><td>audio</td></tr>
     * <tr valign="top"><td>5</td><td>image</td></tr>
     * <tr valign="top"><td>6</td><td>video</td></tr>
     * <tr valign="top"><td>7</td><td>other</td></tr>
     * </table>
     * </p>
     * <p>
     * <table>
     * Transfer encodings (may vary with used library)
     * <tr valign="top"><td>0</td><td>7BIT</td></tr>
     * <tr valign="top"><td>1</td><td>8BIT</td></tr>
     * <tr valign="top"><td>2</td><td>BINARY</td></tr>
     * <tr valign="top"><td>3</td><td>BASE64</td></tr>
     * <tr valign="top"><td>4</td><td>QUOTED-PRINTABLE</td></tr>
     * <tr valign="top"><td>5</td><td>OTHER</td></tr>
     * </table>
     */
    public function fetchStructure($stream, $msg_number, $options = 0)
    {
        return imap_fetchstructure($stream, $msg_number, $options);
    }

    /**
     * Fetch a particular section of the body of the message.
     *
     * @link http://php.net/manual/en/function.imap-fetchbody.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number
     * </p>
     * @param string $section <p>
     * The part number. It is a string of integers delimited by period which
     * index into a body part list as per the IMAP4 specification
     * </p>
     * @param int $options [optional] <p>
     * A bitmask with one or more of the following:
     * <b>FT_UID</b> - The <i>msg_number</i> is a UID
     *
     * @return string a particular section of the body of the specified messages as a
     * text string.
     */
    public function fetchBody($stream, $msg_number, $section, $options = 0)
    {
        return imap_fetchbody($stream, $msg_number, $section, $options);
    }

    /**
     * Read the message body.
     *
     * @link http://php.net/manual/en/function.imap-body.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number
     * </p>
     * @param int $options [optional] <p>
     * The optional <i>options</i> are a bit mask
     * with one or more of the following:
     * <b>FT_UID</b> - The <i>msg_number</i> is a UID
     *
     * @return string the body of the specified message, as a string.
     */
    public function body($stream, $msg_number, $options = 0)
    {
        return imap_body($stream, $msg_number, $options);
    }

    /**
     * Mark a message for deletion from current mailbox.
     *
     * @link http://php.net/manual/en/function.imap-delete.php
     *
     * @param resource $stream
     * @param int $msg_number <p>
     * The message number
     * </p>
     * @param int $options [optional] <p>
     * You can set the <b>FT_UID</b> which tells the function
     * to treat the <i>msg_number</i> argument as an
     * UID.
     * </p>
     */
    public function delete($stream, $msg_number, $options = 0)
    {
        imap_delete($stream, $msg_number, $options);
    }

    /**
     * Sets flags on messages.
     *
     * @link http://php.net/manual/en/function.imap-setflag-full.php
     *
     * @param resource $stream
     * @param string $sequence <p>
     * A sequence of message numbers. You can enumerate desired messages
     * with the X,Y syntax, or retrieve all messages
     * within an interval with the X:Y syntax
     * </p>
     * @param string $flag <p>
     * The flags which you can set are \Seen,
     * \Answered, \Flagged,
     * \Deleted, and \Draft as
     * defined by RFC2060.
     * </p>
     * @param int $options [optional] <p>
     * A bit mask that may contain the single option:
     * <b>ST_UID</b> - The sequence argument contains UIDs
     * instead of sequence numbers
     *
     * @throws \RuntimeException If operation fail
     */
    public function setFlag($stream, $sequence, $flag, $options = null)
    {
        $result = imap_setflag_full($stream, $sequence, $flag, $options);
        if (empty($result)) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Clears flags on messages.
     *
     * @link http://php.net/manual/en/function.imap-clearflag-full.php
     *
     * @param resource $stream
     * @param string $sequence <p>
     * A sequence of message numbers. You can enumerate desired messages
     * with the X,Y syntax, or retrieve all messages
     * within an interval with the X:Y syntax
     * </p>
     * @param string $flag <p>
     * The flags which you can unset are "\\Seen", "\\Answered", "\\Flagged",
     * "\\Deleted", and "\\Draft" (as defined by RFC2060)
     * </p>
     * @param int $options [optional] <p>
     * <i>options</i> are a bit mask and may contain
     * the single option:
     * <b>ST_UID</b> - The sequence argument contains UIDs
     * instead of sequence numbers
     *
     * @throws \RuntimeException If operation fail
     */
    public function clearFlag($stream, $sequence, $flag, $options = null)
    {
        $result = imap_clearflag_full($stream, $sequence, $flag, $options);
        if (empty($result)) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Copy specified messages to a mailbox.
     *
     * @link http://php.net/manual/en/function.imap-mail-copy.php
     *
     * @param resource $stream
     * @param string $msglist <p>
     * <i>msglist</i> is a range not just message
     * numbers (as described in RFC2060).
     * </p>
     * @param string $mailbox <p>
     * The mailbox name, see <b>imap_open</b> for more
     * information
     * </p>
     * @param int $options [optional] <p>
     * <i>options</i> is a bitmask of one or more of
     * <b>CP_UID</b> - the sequence numbers contain UIDS
     *
     * @throws \RuntimeException If operation fail
     */
    public function mailCopy($stream, $msglist, $mailbox, $options = 0)
    {
        $result = imap_mail_copy($stream, $msglist, $mailbox, $options);
        if (empty($result)) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Close an IMAP stream.
     *
     * @link http://php.net/manual/en/function.imap-close.php
     *
     * @param resource $stream
     * @param int $flag [optional] <p>
     * If set to <b>CL_EXPUNGE</b>, the function will silently
     * expunge the mailbox before closing, removing all messages marked for
     * deletion. You can achieve the same thing by using
     * <b>imap_expunge</b>
     * </p>
     *
     * @throws \RuntimeException If operation fail
     */
    public function close($stream, $flag = 0)
    {
        $result = imap_close($stream, $flag);
        if (!$result) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Reopen IMAP stream to new mailbox.
     *
     * @link http://php.net/manual/en/function.imap-reopen.php
     *
     * @param resource $stream
     * @param string $mailbox <p>
     * The mailbox name, see <b>imap_open</b> for more
     * information
     * </p>
     * @param int $options [optional] <p>
     * The <i>options</i> are a bit mask with one or more of
     * the following:
     * <b>OP_READONLY</b> - Open mailbox read-only
     * @param int $n_retries [optional] <p>
     * Number of maximum connect attempts
     * </p>
     *
     * @throws \RuntimeException
     */
    public function reopen($stream, $mailbox, $options = 0, $n_retries = 0)
    {
        $result = imap_reopen($stream, $mailbox, $options, $n_retries);
        if (!$result) {
            throw new \RuntimeException(imap_last_error());
        }
    }

    /**
     * Read an overview of the information in the headers of the given message.
     *
     * @link http://php.net/manual/en/function.imap-fetch-overview.php
     *
     * @param resource $stream
     * @param string $sequence <p>
     * A message sequence description. You can enumerate desired messages
     * with the X,Y syntax, or retrieve all messages
     * within an interval with the X:Y syntax
     * </p>
     * @param int $options [optional] <p>
     * <i>sequence</i> will contain a sequence of message
     * indices or UIDs, if this parameter is set to
     * <b>FT_UID</b>.
     * </p>
     *
     * @return array an array of objects describing one message header each.
     * The object will only define a property if it exists. The possible
     * properties are:
     * subject - the messages subject
     * from - who sent it
     * to - recipient
     * date - when was it sent
     * message_id - Message-ID
     * references - is a reference to this message id
     * in_reply_to - is a reply to this message id
     * size - size in bytes
     * uid - UID the message has in the mailbox
     * msgno - message sequence number in the mailbox
     * recent - this message is flagged as recent
     * flagged - this message is flagged
     * answered - this message is flagged as answered
     * deleted - this message is flagged for deletion
     * seen - this message is flagged as already read
     * draft - this message is flagged as being a draft
     */
    public function fetchOverview($stream, $sequence, $options = 0)
    {
        return imap_fetch_overview($stream, $sequence, $options);
    }
}