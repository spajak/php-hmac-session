<?php

declare(strict_types=1);

namespace Spajak\Session;

use LengthException;
use DomainException;
use Spajak\Session\Serializer\JsonSerializer;
use Spajak\Session\Serializer\MessagePackSerializer;

/**
 * Simple, fast, secure, storage-less PHP session.
 * No ids, no storage, not locks.
 *
 * Session is serialized and signed into a string of the form: `payload.expire.hash`.
 * Session is loaded and validated for authenticity and split into payload and expire.
 * Then, if not expired, it is unserialized and resumed.
 *
 * Session has to be committed explicitly with `::commit()` method.
 *
 * Limits:
 * Cookie size is limited to 4096 bytes. Therefore keep the session data as small as possible.
 * Use `::getSession::getSize()` method to get the current session size at any time, if unsure.
 *
 */
final class Session
{
    private $carrier;
    private $authenticator;
    private $serializer;
    //
    private $ttl = 1800;
    private $loaded;
    private $data = [];

    /**
     * Creates the session.
     * Serializer is optional and defaults to `msgpack` if available, else `json`.
     */
    public function __construct(
        SessionCarrierInterface $carrier,
        SessionAuthenticatorInterface $authenticator,
        SessionSerializerInterface $serializer = null
    ) {
        $this->carrier = $carrier;
        $this->authenticator = $authenticator;
        $this->serializer = $serializer;
        if (!$this->serializer) {
            try {
                $this->serializer = new MessagePackSerializer;
            } catch (DomainException $e) {
                $this->serializer = new JsonSerializer;
            }
        }
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

    public function setTtl(int $ttl) : self
    {
        if ($ttl <= 0) {
            throw new LengthException('Ttl must be greater than zero');
        }
        $this->ttl = $ttl;
        return $this;
    }

    public function getTtl() : int
    {
        return $this->ttl;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getCarrier() : SessionCarrierInterface
    {
        return $this->carrier;
    }

    public function getAuthenticator() : SessionAuthenticatorInterface
    {
        return $this->authenticator;
    }

    public function getSerializer() : SessionSerializerInterface
    {
        return $this->serializer;
    }

    public function getSession() : Message
    {
        $this->load();
        $message = new Message;
        $message->payload = $this->serializer->serialize($this->data);
        $message->expire = time() + $this->ttl;
        $this->authenticator->sign($message);
        return $message;
    }

    public function getLoadedSession() : Message
    {
        $this->load();
        return $this->loaded;
    }

    /**
     * Loads the session from the carrier.
     */
    public function load() : void
    {
        if (isset($this->loaded)) {
            return;
        }
        $this->loaded = $this->carrier->fetch();
        if (null === $this->loaded->session) {
            return;
        }
        $this->authenticator->validate($this->loaded);
        if ($this->loaded->state !== Message::VALID) {
            return;
        }
        $this->data = $this->serializer->unserialize($this->loaded->payload);
    }

    /**
     * Commit the session.
     */
    public function commit() : void
    {
        $this->carrier->store($this->getSession());
    }

    /**
     * Destroy the session.
     */
    public function destroy() : void
    {
        $this->clear();
        $this->carrier->destroy();
    }
}
