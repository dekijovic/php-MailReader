<?php

class MailReader
{
    protected $conn;
    protected $email;
    protected $host;
    protected $port;
    protected $password;


    /**
     * MailReader constructor.
     * @param null $host
     * @param null $email
     * @param null $password
     * @param null $port
     */
    public function __construct($host, $email, $password, $port)
    {
        $this->email = $email;
        $this->password = $password;
        if(substr($host, 0,1) == '}') {
            $this->host = $host;
        }else{
            $this->host = '{' . $host . ':' . $port . '/ssl}INBOX';
        }
        $this->port = $port;
        $this->conn = imap_open($this->host, $this->email, $this->password) or die('Cannot connect to Mail: ' . imap_last_error());
        if (!preg_match("/Resource.id.*/", (string) $this->conn)) {
            return $this->conn; //return error message
        }
    }

    /**
     * @return array|false
     */
    public function getAllMessages():? array
    {
        return imap_search($this->conn, 'ALL');
    }

    /**
     * @return array|false
     */
    public function getAllUnseenMessages() :? array
    {
        $emails = imap_search($this->conn, 'UNSEEN');

        return $emails;
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
     * @param $numEmail
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

}