<?php

namespace Spajak\Session;

final class Message
{
    const VALID = 'valid';
    const EXPIRED = 'expired';
    const INVALID_SIZE = 'size';
    const INVALID_FORMAT = 'format';
    const INVALID_HASH = 'hash';

    public $session;
    public $state;
    public $payload;
    public $expire;

    public function getSize() : int
    {
        return null !== $this->session ? strlen($this->session) : 0;
    }

    public function isValid() : bool
    {
        return null !== $this->session ? $this->state === self::VALID : true;
    }

    public function __toString()
    {
        return $this->session ?? '';
    }
}
