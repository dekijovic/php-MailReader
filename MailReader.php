<?php

/**
 * Class MailReader
 *
 * Class Mail client that works with Imap php extension
 *
 * @author Dejan Jovic
 * @licence http://mit-license.org/
 *
 * @link https://github.com/dekijovic/php-MailReader
 */
class MailReader
{
    const CRITERIA = ['ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
        'NEW', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO', 'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN'];

    /** @var false|resource  */
    protected $conn;
    /** @var string|null  */
    protected $host;


    /**
     * MailReader constructor.
     * @param null $host
     * @param null $email
     * @param null $password
     * @param null $port
     */
    public function __construct($host, $email, $password, $port)
    {
        if(substr($host, 0,1) == '}') {
            $this->host = $host;
        }else{
            $this->host = '{' . $host . ':' . $port . '/ssl}INBOX';
        }
        $this->conn = imap_open($this->host, $email, $password) or die('Cannot connect to Mail: ' . imap_last_error());
    }

    /**
     * @param string $criteria
     * @return array|null
     */
    public function getAllMessages($criteria = 'ALL'):? array
    {
        if(array_search($criteria, self::CRITERIA)){
            $arr = imap_search($this->conn, $criteria);
        }

        return $arr ?? null;
    }

    /**
     * Get Sender
     * @param $numEmail
     * @return false|string
     */
    public function getSender($numEmail){

        $overview  = imap_fetch_overview($this->conn, $numEmail);
        $mail = explode('<',$overview[0]->from);
        return substr($mail[1], 0,-1);
    }

    /**
     * Fetch content form mail message.
     *
     * @param $numEmail
     * @return array
     */
    public function fetchContent($numEmail)
    {
        $attachments = [];
        $body = null;
        $contents = imap_fetchstructure($this->conn, $numEmail);
        foreach ($contents->parts as $index => $content) {

            if($content->ifdisposition && $content->disposition == 'attachment'){
                $attachments[] = ['content '=> $content, 'index' => $index];
            }

            if($content->subtype ==  'HTML'){
                $body = $content;
            }
        }
        return ["attachment" => $attachments, 'body' => $body, 'mailNumber' => $numEmail];
    }

    /**
     * Download Attachment.
     *
     * @param $content
     * @param $numEmail
     * @param $index
     */
    public function downloadAttachment($content, $numEmail, $index){

        $name = $this->getFileName($content);
        if(!is_dir(realpath('./mail_download'))){
            mkdir(realpath('./mail_download'));
        }
        $file = realpath('./mail_download').'/'.$name['filename'];
        $fp = fopen($file, "w+");
        $data = $this->getEncodedContent($content, $numEmail, $index);
        fwrite($fp, $data);
        fclose($fp);
        chmod($file, 0755);
    }

    /**
     * @param $attachmentPart
     * @return array
     */
    private function getFileName($attachmentPart): array
    {
        $filename = null;
        $charset = null;
        foreach ($attachmentPart->dparameters as $dp){
            if($dp->attribute = 'filename'){
                $filename = $dp->value;
            }
        }
        foreach ($attachmentPart->parameters as $p){
            if($p->attribute = 'charset'){
                $charset = $p->value;
            }
            if($filename == null){
                if($p->attribute = 'name'){
                    $filename = $p->value;
                }
            }
        }
        $arr = ['filename' => $filename, 'charset' => $charset];
        return $arr;
    }

    /**
     * @param $attachmentPart
     * @param $numEmail
     * @param $partIndex
     * @return false|string
     */
    public function getEncodedContent($attachmentPart, $numEmail, $partIndex):? string
    {
        $data = imap_fetchbody($this->conn, $numEmail, $partIndex+1);
        if($attachmentPart->encoding = 3){
            return base64_decode($data);
        }else if($attachmentPart->encoding = 4){
            return quoted_printable_decode($data);
        }else{
            return $data;
        }
    }

    /**
     * Create mailbox and subscribe on
     *
     * @param string $mailbox Name of mailbox
     */
    private function createMailbox($mailbox)
    {
        $mailbox = imap_getmailboxes($this->conn, $this->host.$mailbox, '*');
        if(!$mailbox) {
            imap_createmailbox($this->conn, $this->host.$mailbox);
            imap_subscribe( $this->conn, $this->host.$mailbox );
        }
    }

    /**
     * Open Mailbox
     * @param $mailbox
     */
    public function openMailbox($mailbox)
    {
        imap_reopen($this->conn, $this->host.$mailbox);
    }

    /**
     * Move mail to different folder.
     *
     * @param $num Email Number in current mailbox
     * @param $mailbox Mailbox destination
     */
    public function moveEmailTo($num, $mailbox)
    {
        imap_mail_move($this->conn, $num,$mailbox);
        imap_expunge($this->conn);

    }

    /**
     * Close mail connection
     */
    public function close()
    {
        imap_close($this->conn);
    }

}
