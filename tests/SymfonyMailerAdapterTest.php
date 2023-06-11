<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Notifications\Smtp\Tests;

use Drewlabs\Envoyer\Contracts\NotificationResult;
use Drewlabs\Envoyer\Drivers\Smtp\SymfonyMailerAdapter;
use Drewlabs\Envoyer\Mail;
use Drewlabs\Envoyer\SMTPServer;
use PHPUnit\Framework\TestCase;

class SymfonyMailerAdapterTest extends TestCase
{
    public function test_symfony_mailer_send_request()
    {
        $config = require __DIR__.'/contents/config.php';

        $mail = Mail::new()
            ->from($config['email'])
            ->to('asmyns.platonnas29@gmail.com')
            ->bCc('azandrewdevelopper@gmail.com')
            ->subject('Platform Registration')
            ->attach(new \SplFileInfo(__DIR__.'/contents/bordereau.pdf'))
            ->content(require __DIR__.'/contents/mail.php');

        $smtpServer = new SMTPServer($config['host'], $config['port'], $config['user'], $config['password']);
        $adapter = new SymfonyMailerAdapter($smtpServer->withEncryption('tls'));

        $result = $adapter->sendRequest($mail);

        // Assert
        $this->assertInstanceOf(NotificationResult::class, $result);
        $this->assertTrue($result->isOk());
    }

    public function test_symfony_mailer_send_request_without_attachment()
    {
        $config = require __DIR__.'/contents/config.php';
        $mail = Mail::new()
            ->from($config['email'])
            ->to('azandrewdevelopper@gmail.com')
            ->subject('No Attachment Registration')
            ->content(require __DIR__.'/contents/mail.php');

        $smtpServer = new SMTPServer($config['host'], $config['port'], $config['user'], $config['password']);
        $adapter = new SymfonyMailerAdapter($smtpServer->withEncryption('tls'));

        $result = $adapter->sendRequest($mail);

        // Assert
        $this->assertInstanceOf(NotificationResult::class, $result);
        $this->assertTrue($result->isOk());

    }
}
