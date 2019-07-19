<?php

declare(strict_types=1);

namespace Spajak\Session;

/**
 * Session serializer interface.
 */
interface SessionSerializerInterface
{
    /**
     * Serialize an array into a string.
     */
    public function serialize(array $value) : string;

    /**
     * Unserialize a string into an array.
     * Throw `SerializerException` in case of an error.
     */
    public function unserialize(string $value) : array;
}
