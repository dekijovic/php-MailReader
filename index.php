<?php

include_once 'MailReader.php';

$host = 'mail.test.com';
$username = 'test';
$password = "123456";
$port = 993;

$reader = new MailReader($host, $username, $password, $port);


$allMessages = $reader->getAllMessages();
$newMessages = $reader->getAllMessages('UNSEEN');


foreach ($newMessages as $messageNum){
    $parts = $reader->fetchContent($messageNum);
    foreach ($parts['attachment'] as $part){
        $reader->downloadAttachment($part['content'], $messageNum, $part['index']);
    }
    $bodyMessage = $reader->getEncodedContent($parts['body'], $messageNum, 0);
}



$newMessages = $reader->getAllMessages('UNSEEN');
$lastEmail = $newMessages[array_key_last($newMessages)];
$reader->createMailbox('Business');
$reader->moveEmailTo($lastEmail, 'Business');
$reader->openMailbox( 'Business');
$BusinessMessages = $reader->getAllMessages();