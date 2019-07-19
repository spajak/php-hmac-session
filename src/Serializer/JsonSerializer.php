<?php

declare(strict_types=1);

namespace Spajak\Session\Serializer;

use Spajak\Session\SessionSerializerInterface;
use Spajak\Session\Exception\SerializerException;
use JsonException;

class JsonSerializer implements SessionSerializerInterface
{
    public function serialize(array $value) : string
    {
        try {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new SerializerException('Unable to serialize value to json: '.$e->getMessage());
        }
    }

    public function unserialize(string $value) : array
    {
        if (empty($value)) {
            return [];
        }
        try {
            $result = json_decode($value, true, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new SerializerException('Unable to unserialize json: '.$e->getMessage());
        }
        if (empty($result)) {
            return [];
        }
        if (!is_array($result)) {
            throw new SerializerException('Unserialized session is not an array');
        }
        return $result;
    }
}
