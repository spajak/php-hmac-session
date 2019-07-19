<?php

declare(strict_types=1);

namespace Spajak\Session;

interface SessionSerializerInterface
{
    public function serialize(array $value) : string;

    public function unserialize(string $value) : array;
}
