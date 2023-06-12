# Envoyer SMTP Drivers

SMTP drivers provide implementations of `drewlabs/envoyer` driver or client interface that uses smtp protocol internally to send text message to send email messages.

## Usage

```php
use Drewlabs\Envoyer\Drivers\Smtp\SymfonyMailerAdapter;
use Drewlabs\Envoyer\Mail;
use Drewlabs\Envoyer\SMTPServer;

$config = require  __DIR__ . '/contents/config.php';

// Create mail instance
$mail = Mail::new()
    ->from($config['email'])
    ->to('azandrewdevelopper@gmail.com')
    ->subject('No Attachment Registration')
    ->content(require __DIR__ . '/contents/mail.php');

// Create SMTP server configuration object
$smtpServer = new SMTPServer($config['host'], $config['port'], $config['user'], $config['password']);

// Create Symfony mailer object
$adapter = new SymfonyMailerAdapter($smtpServer->withEncryption('tls'));

// Send mail request
$result = $adapter->sendRequest($mail);
```
