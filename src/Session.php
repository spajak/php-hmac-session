<?php

declare(strict_types=1);

namespace Spajak\Session;

use InvalidArgumentException;
use DomainException;
use LogicException;

/**
 * Simple, fast, secure, storage-less PHP session.
 * No ids, no storage, not locks.
 *
 * How it works:
 * The session is serialized to a string then hashed, and finally it looks like this:
 *   `payload.expire.hash` (or `message.hash` in short).
 * Where:
 *   `payload` - Base64 encoded serialized session array.
 *   `expire`  - Unix timestamp that tells when the session will expire.
 *   `hash`    - HMAC hash of the `payload.expire` string (`payload` before base64 encode).
 * In this form the session is sent as a whole to the client (with a `set-cookie` response header).
 * When the client sends the cookie with a request, session is validated for authenticity,
 * then `expire` time is checked. If all is green, the session is unserialized back to PHP array.
 *
 * Limits:
 * Cookie size is limited to 4096 bytes. Therefore keep the session data as small as possible.
 * Use getSize() method to calculate current session size, if unsure.
 */
final class Session
{
    private $serializers = ['igbinary', 'msgpack', 'json', 'auto'];
    private $options;
    private $carrier;
    private $serializer;
    private $separator = '.';
    private $loaded = false;
    private $supply;
    private $data = [];

    /**
     * Creates the session.
     *
     * Options:
     *   `name`           - Session name (cookie name) (`session` by default).
     *   `key`            - Secret key used for `HMAC` hash. Must be a string no less that 32 bytes long (required).
     *                      Should be a random string. You can get one with `$ openssl rand -hex 32`.
     *   `serializer`     - Serializer to use. Valid options are `igbinary`, `msgpack`, `json`, `auto` (default, check what is available).
     *   `ttl`            - Session Time To Live in seconds. Default is 1800.
     *   `hmac_algorithm` - Not recommended to change this. Default is `sha256` and this should be fine.
     *
     * Serializers:
     *   `igbinary` - [igbinary serializer PHP extension](https://github.com/igbinary/igbinary).
     *   `msgpack`  - [MessagePack binary serializer PHP extension](https://github.com/msgpack/msgpack-php).
     *   `json`     - PHP json_encode() and json_decode() functions. This is a fallback serializer if none of the above is usable.
     *
     */
    public function __construct(array $options, SessionCarrierInterface $carrier)
    {
        $defaults = [
            'name' => 'session',
            'key' => null,
            'serializer' => 'auto',
            'ttl' => 1800,
            'hmac_algorithm' => 'sha256'
        ];
        $this->options = array_merge($defaults, $options);
        $this->carrier = $carrier;
        $key = $this->options['key'];
        if (null == $key or strlen($key) < 32) {
            throw new InvalidArgumentException('Key length should be at least 32 bytes');
        }
        $ttl = $this->options['ttl'];
        if (!is_int($ttl) or $ttl < 1) {
            throw new InvalidArgumentException('Ttl must be an integer greater than zero');
        }
        $algo = $this->options['hmac_algorithm'];
        if (!$algo or !in_array($algo, hash_hmac_algos(), true)) {
            throw new InvalidArgumentException(sprintf('Hash algorithm "%s" is not valid', $algo));
        }
        $this->setSerializer();
    }

    public function set(string $key, $value) : self
    {
        $this->load();
        $this->data[$key] = $value;
        return $this;
    }

    public function has(string $key) : bool
    {
        $this->load();
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function delete(string $key) : self
    {
        $this->load();
        unset($this->data[$key]);
        return $this;
    }

    public function clear() : self
    {
        $this->load();
        $this->data = [];
        return $this;
    }

    public function getTtl() : int
    {
        return (int) $this->options['ttl'];
    }

    public function getCarrier() : SessionCarrierInterface
    {
        return $this->carrier;
    }

    public function getSupply() : ?array
    {
        $this->load();
        return $this->supply;
    }

    public function getSession() : ?string
    {
        $this->load();
        if (null !== $payload = $this->serialize($this->data)) {
            return $this->hashPayload($payload);
        }
        return null;
    }

    public function getSessionSize() : int
    {
        if (null !== $session = $this->getSession()) {
            return strlen($session);
        }
        return 0;
    }

    /**
     * Commit the session.
     */
    public function commit() : void
    {
        if (null === $session = $this->getSession()) {
            return;
        }
        $this->carrier->store($session, $this->getTtl());
    }

    public function destroy() : void
    {
        $this->supply = null;
        $this->clear();
        $this->carrier->destroy();
    }

    public function serialize(array $data) : ?string
    {
        $result = $this->serializer[0]($data);
        if ($result and is_string($result)) {
            return $result;
        }
    }

    public function unserialize(string $payload) : ?array
    {
        $result = $this->serializer[1]($payload);
        if ($result and is_array($result)) {
            return $result;
        }
    }

    /**
     * Loads the session from the cookie.
     */
    private function load() : void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        if (null === $session = $this->carrier->fetch()) {
            return;
        }
        $this->supply($session);
        if (!$this->supply) {
            return;
        }
        if (true !== $this->supply[2]) {
            return;
        }
        if (null !== $data = $this->unserialize($this->supply[0])) {
            $this->data = $data;
        }
    }

    /**
     * HMAC hash payload + expire timestamp.
     * Return base64 encoded message + expire timestamp + hash.
     */
    private function hashPayload(string $payload) : string
    {
        $expire = time() + $this->options['ttl'];
        $algo = $this->options['hmac_algorithm'];
        $message = base64_encode($payload).$this->separator.$expire;
        $key = $this->options['key'];
        // lowercase hexits
        $hash = hash_hmac($algo, $payload.$this->separator.$expire, $key);
        return $message.$this->separator.$hash;
    }

    /**
     * Validate and decode value (message + hash) to payload and timestamp.
     */
    private function supply(string $value) : void
    {
        if (empty($value) or !is_string($value)) {
            return;
        }
        $value = trim($value);
        // payload + separator + time + separator + hash
        if (strlen($value) < 16) {
            return;
        }
        $s = preg_quote($this->separator);
        // base64 - unix timestamp - lowercase hex
        if (!preg_match('`^([a-zA-Z\d\+/]+[=]{0,2})'.$s.'(\d+)'.$s.'([0-9a-f]+)$`', $value, $matches)) {
            return;
        }
        $payload = base64_decode($matches[1]);
        $expire = (int) $matches[2];
        $hash = $matches[3];
        $algo = $this->options['hmac_algorithm'];
        $key = $this->options['key'];
        if (hash_equals(hash_hmac($algo, $payload.$this->separator.$expire, $key), $hash)) {
            $this->supply = [
                $payload,
                $expire,
                $expire > time() - $this->getTtl()
            ];
        }
    }

    private function setSerializer() : void
    {
        $serializer = $this->options['serializer'];
        if (!$serializer or !in_array($serializer, $this->serializers, true)) {
            throw new InvalidArgumentException(sprintf('Serializer "%s" is not supported', $serializer));
        }
        $serializers = $this->getAvailableSerializers();
        if (empty($serializers)) {
            throw new LogicException('No usable serializer found! This should not happen!');
        }
        if ($serializer !== 'auto') {
            if (!isset($serializers[$serializer])) {
                throw new DomainException(sprintf('Serializer "%s" is not usable', $serializer));
            }
            $this->serializer = $serializers[$serializer];
        } else {
            $this->serializer = array_shift($serializers);
        }
    }

    private function getAvailableSerializers() : array
    {
        $serializers = [];
        foreach ($this->serializers as $name) {
            $method = 'get'.ucfirst($name).'Serializer';
            if (method_exists($this, $method)) {
                $serializer = $this->$method();
                if ($serializer[0] === true) {
                    $serializers[$name] = [$serializer[1], $serializer[2]];
                }
            }
        }
        return $serializers;
    }

    private function getJsonSerializer() : array
    {
        return [
            true,
            function(array $data) {
                return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },
            function(string $data) {
                return @json_decode($data, true);
            }
        ];
    }

    private function getIgbinarySerializer() : array
    {
        return [
            extension_loaded('igbinary'),
            function(array $data) {
                return @igbinary_serialize($data);
            },
            function(string $data) {
                return @igbinary_unserialize($data);
            }
        ];
    }

    private function getMsgpackSerializer() : array
    {
        return [
            extension_loaded('msgpack'),
            function(array $data) {
                return @msgpack_pack($data);
            },
            function(string $data) {
                return @msgpack_unpack($data);
            }
        ];
    }
}
