<?php

declare(strict_types=1);

namespace Spajak\Session\Carrier;

use Spajak\Session\SessionCarrierInterface;

class MemoryCarrier implements SessionCarrierInterface
{
    protected $data;

    public function __construct(string $data = null)
    {
        $this->input = $data;
    }

    public function __toString()
    {
        return $this->data ?? '';
    }

    public function fetch() : ?string
    {
        return $this->data;
    }

    public function store(string $data, int $ttl = 0) : void
    {
        $this->data = $data;
    }

    public function destroy() : void
    {
        $this->data = null;
    }
}
