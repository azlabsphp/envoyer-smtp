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

use Drewlabs\Envoyer\Contracts\NotificationResult;

class Result implements NotificationResult
{
    /** @var \DateTimeInterface */
    private $at;

    /** @var int */
    private $id;

    /** @var bool */
    private $isOk;

    /** @var string|null */
    private $exception;

    /**
     * Creates class instance.
     *
     * @param \Throwable $exception
     */
    public function __construct(\DateTimeInterface $at = null, int $id = null, \Throwable $exception = null)
    {
        $this->at = $at ?? new \DateTimeImmutable();
        $this->id = $id ?? (int) (time().random_int(1000, 100000));
        $this->exception = $exception;
        $this->isOk = null === $exception;
    }

    /**
     * Creates an `ok` result.
     *
     * @return static
     */
    public static function ok(\DateTimeInterface $at = null, int $id = null)
    {
        return new static($at, $id);
    }

    /**
     * Creates an `error` result.
     *
     * @param \Throwable $exception
     *
     * @return static
     */
    public static function exception(\Throwable $exception = null, \DateTimeInterface $at = null, int $id = null)
    {
        return new static($at, $id, $exception);
    }

    public function date()
    {
        return $this->at->format(\DateTimeImmutable::ATOM);
    }

    public function id()
    {
        return $this->id;
    }

    public function isOk()
    {
        return $this->isOk;
    }

    /**
     * Set the exception instance in case of exception.
     *
     * @return \Throwable|null $e
     */
    public function getError()
    {
        return $this->exception;
    }
}
