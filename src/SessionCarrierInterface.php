<?php

declare(strict_types=1);

namespace Spajak\Session;

interface SessionCarrierInterface
{
    public function fetch() : ?string;

    public function store(string $data, int $ttl = 0) : void;

    public function destroy() : void;
}
