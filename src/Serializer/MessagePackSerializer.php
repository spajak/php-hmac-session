<?php

declare(strict_types=1);

namespace Spajak\Session\Serializer;

use Spajak\Session\SessionSerializerInterface;
use Spajak\Session\Exception\SerializerException;
use DomainException;

class MessagePackSerializer implements SessionSerializerInterface
{
    public function __construct()
    {
        if (!extension_loaded('msgpack')) {
            throw new DomainException('Required PHP module "msgpack" is not loaded');
        }
    }

    public function serialize(array $value) : string
    {
        return msgpack_pack($value);
    }

    public function unserialize(string $value) : array
    {
        $result = @msgpack_unpack($value);
        if (null === $result) {
            throw new SerializerException('Unserializing session failed (msgpack)');
        }
        if (!is_array($result)) {
            throw new SerializerException('Unserialized session is not an array (msgpack)');
        }
        return $result;
    }
}
