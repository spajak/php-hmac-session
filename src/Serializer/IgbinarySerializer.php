<?php

declare(strict_types=1);

namespace Spajak\Session\Serializer;

use Spajak\Session\SessionSerializerInterface;
use Spajak\Session\Exception\SerializerException;
use DomainException;

class IgbinarySerializer implements SessionSerializerInterface
{
    public function __construct()
    {
        if (!extension_loaded('igbinary')) {
            throw new DomainException('Required PHP module "igbinary" is not loaded');
        }
    }

    public function serialize(array $value) : string
    {
        return igbinary_serialize($value);
    }

    public function unserialize(string $value) : array
    {
        $result = @igbinary_unserialize($value);
        if (null === $result) {
            throw new SerializerException('Unserializing session failed (igbinary)');
        }
        if (!is_array($result)) {
            throw new SerializerException('Unserialized session is not an array (igbinary)');
        }
        return $result;
    }
}
