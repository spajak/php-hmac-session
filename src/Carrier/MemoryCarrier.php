<?php

declare(strict_types=1);

namespace Spajak\Session\Carrier;

use Spajak\Session\SessionCarrierInterface;
use Spajak\Session\Message;

class MemoryCarrier implements SessionCarrierInterface
{
    protected $data;

    public function __construct(?string $data = null)
    {
        $this->data = $data;
    }

    public function __toString()
    {
        return $this->data ?? '';
    }

    public function fetch() : Message
    {
        $m = new Message;
        $m->session = $this->data;
        return $m;
    }

    public function store(Message $message) : void
    {
        $this->data = $message->session;
    }

    public function destroy() : void
    {
        $this->data = null;
    }
}
