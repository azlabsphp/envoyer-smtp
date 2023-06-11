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

namespace Drewlabs\Envoyer\Drivers\Smtp;

use Drewlabs\Envoyer\Contracts\AttachedAddressesAware;
use Drewlabs\Envoyer\Contracts\AttachmentsAware;
use Drewlabs\Envoyer\Contracts\ClientInterface;
use Drewlabs\Envoyer\Contracts\ClientSecretKeyAware;
use Drewlabs\Envoyer\Contracts\EncryptionAware;
use Drewlabs\Envoyer\Contracts\NotificationInterface;
use Drewlabs\Envoyer\Contracts\NotificationResult;
use Drewlabs\Envoyer\Contracts\ServerConfigInterface;
use Drewlabs\Envoyer\Contracts\SubjectAware;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class SymfonyMailerAdapter implements ClientInterface
{
    /**
     * List of encryption methods that enforce SSL.
     */
    public const SSL_ENCRYPTION = ['tls'];

    /**
     * @var ServerConfigInterface&ClientSecretKeyAware
     */
    private $server;

    /**
     * Creates class instance.
     *
     * @param ServerConfigInterface&ClientSecretKeyAware $server
     */
    public function __construct(ServerConfigInterface $server)
    {
        $this->server = $server;
    }

    public function sendRequest(NotificationInterface $instance): NotificationResult
    {
        try {
            // We create the mail transport
            $transport = Transport::fromDsn($this->createDsn($this->server));

            // Create the mailer instance
            $mailer = new Mailer($transport);

            // Create the mail instance
            $message = (new Email())
                ->from((string) $instance->getSender())
                ->to((string) $instance->getReceiver())
                ->priority(Email::PRIORITY_HIGH)
                ->html($content = $instance->getContent())
                ->text(preg_replace("/\n\s+/", "\n", rtrim(html_entity_decode(strip_tags($content)))));

            // Set the mail subject
            if ($instance instanceof SubjectAware) {
                $message = $message->subject($instance->getSubject());
            }

            // Set the attached addresses
            if ($instance instanceof AttachedAddressesAware) {
                $message = $message->addBcc(...array_map(static function ($address) {
                    return (string) $address;
                }, $instance->getAttachedAddresses()));
            }

            // Set the attachments
            if ($instance instanceof AttachmentsAware) {
                foreach ($this->getAttachements($instance) as $attachment) {
                    $message = $message->attach(...$attachment);
                }
            }

            // Send the email
            $mailer->send($message);

            return Result::ok();

            // Case Error is catched
        } catch (\Throwable $e) {
            return Result::exception($e);
        }
    }

    /**
     * Creates attachment traversable instance.
     *
     * @throws RuntimeException
     *
     * @return \Traversable<array<string,string,string>>
     */
    private function getAttachements(AttachmentsAware $instance)
    {
        foreach ($instance->getAttachments() as $attachment) {
            if ($attachment instanceof StreamInterface) {
                yield [$attachment->getContents(), null, ($mimeType = $this->getStreamMimeType($attachment)) ? $mimeType : null];
            } elseif ($attachment instanceof \SplFileInfo && $attachment->isFile() && $attachment->isReadable()) {
                yield [file_get_contents($attachment->getRealPath()), $attachment->getFilename(), ($mimeType = $this->getFinfoMimeType($attachment)) ? $mimeType : null];
            } else {
                yield [$attachment];
            }
        }
    }

    /**
     * Get stream mime type.
     *
     * @return string|false|null
     */
    private function getStreamMimeType(StreamInterface $stream)
    {
        if (\function_exists('mime_content_type')) {
            $tmp = $stream->getMetadata('uri');

            return mime_content_type($tmp) ?? null;
        }

        return null;
    }

    /**
     * File info to mime type.
     *
     * @return string|false|null
     */
    private function getFinfoMimeType(\SplFileInfo $info)
    {
        if (\function_exists('finfo_file') && \function_exists('finfo_open') && \function_exists('finfo_close')) {
            $finfo = finfo_open();
            $info = finfo_file($finfo, $info->getRealPath(), \FILEINFO_MIME_TYPE);
            finfo_close($finfo);

            return $info;
        }

        return null;
    }

    /**
     * Creates mail transport dns.
     *
     * @param ClientSecretKeyAware&ServerConfigInterface $server
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function createDsn(ClientSecretKeyAware $server)
    {
        if (null === $server) {
            throw new \InvalidArgumentException('Notifiable is not correctly initialized');
        }
        $user = urlencode($server->getClientId());
        $password = urlencode($server->getClientSecret());
        $host = $server->getHost();
        $port = $server->getHostPort() ?? 25;
        if ((null === $host) || (null === $user) || (null === $password)) {
            return 'sendmail://default';
        }

        // #region Enforce the mailer to use tls encryption if the server encryption is tls
        $protocol = 'smtp';
        if ($server instanceof EncryptionAware && \in_array(strtolower($server->getEncryption()), static::SSL_ENCRYPTION, true)) {
            $protocol = 'smtps';
        }
        // #endregion Enforce the mailer to use tls encryption if the server encryption is tls

        return "$protocol://$user:$password@$host:$port";
    }
}
