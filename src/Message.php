<?php

namespace Spajak\Session;

final class Message
{
    public $session;
    public $valid;
    public $payload;
    public $expire;

    public function getSize() : int
    {
        return null !== $this->session ? strlen($this->session) : 0;
    }

    public function __toString()
    {
        return $this->session ?? '';
    }
}
