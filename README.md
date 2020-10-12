# MailReader

* Mail Reader client library has a purpose to connect to some mailbox using php and php_imap extension 
witch need to be installed and enabled

* MailReader will retrieve all messages, all unseen messages nad fetch content if there is attachment it will download it into ./mail_download directory

* In Dockerfile you can see how to setup php_imap extension when using docker

* In index.php you can see simple implementation of downloading attachments from unseen messages;
