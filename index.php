<?php

include_once 'MailReader.php';

$host = 'mailbox.test.com';
$user = 'tzar';
$user = "123456";
$port = 993; // IMAP

$reader = new MailReader($host, $user, $user, $port);

$newMessages = $reader->getAllMessages('UNSEEN');

foreach ($newMessages as $messageNum){
    $parts = $reader->fetchContent($messageNum);
    foreach ($parts['attachment'] as $part){
        $reader->downloadAttachment($part['content'], $messageNum, $part['index']);
    }

    $bodyMessage = $reader->getEncodedContent($parts['body'], $messageNum, 0);
}