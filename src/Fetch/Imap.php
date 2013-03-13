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
            throw new \RuntimeException(join("\n", imap_errors()));
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
            throw new \RuntimeException(join("\n", imap_errors()));
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