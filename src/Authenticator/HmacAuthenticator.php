<?php

declare(strict_types=1);

namespace Spajak\Session\Authenticator;

use Spajak\Session\SessionAuthenticatorInterface;
use Spajak\Session\Message;
use LengthException;
use DomainException;

class HmacAuthenticator implements SessionAuthenticatorInterface
{
    protected $algo = 'sha256';
    protected $key;
    protected $separator;
    protected $inputSizeMin = 16;
    protected $inputSizeMax = 524288;

    public function __construct(string $key, string $separator = '.')
    {
        $this->key = $key;
        $this->separator = $separator;
        if (strlen($this->key) < 32) {
            throw new LengthException('Key length should be at least 32 bytes');
        }
        if (!in_array($this->algo, hash_hmac_algos(), true)) {
            throw new DomainException(sprintf('Hash algorithm "%s" is not available', $this->algo));
        }
    }

    public function sign(Message $message) : void
    {
        $value = $message->payload.$this->separator.$message->expire;
        $hash = hash_hmac($this->algo, $value, $this->key);
        $msg = base64_encode($message->payload).$this->separator.$message->expire;
        $message->session = $msg.$this->separator.$hash;
        $message->state = Message::VALID;
    }

    public function validate(Message $message) : void
    {
        $length = strlen($message->session);
        // payload + separator + expire + separator + hash
        if ($length < $this->inputSizeMin or $length > $this->inputSizeMax) {
            $message->state = Message::INVALID_SIZE;
            return;
        }
        $s = preg_quote($this->separator);
        // base64 + unix timestamp + lowercase hex
        $pattern = '`^([a-zA-Z\d\+/]+[=]{0,2})'.$s.'(\d+)'.$s.'([0-9a-f]+)$`';
        if (!preg_match($pattern, $message->session, $matches)) {
            $message->state = Message::INVALID_FORMAT;
            return;
        }
        $message->payload = base64_decode($matches[1]);
        $message->expire = (int) $matches[2];
        if ($message->expire < time()) {
            $message->state = Message::EXPIRED;
            return;
        }
        $hash = $matches[3];
        $value = $message->payload.$this->separator.$message->expire;
        if (hash_equals(hash_hmac($this->algo, $value, $this->key), $hash)) {
            $message->state = Message::VALID;
        } else {
            $message->state = Message::INVALID_HASH;
        }
    }
}
