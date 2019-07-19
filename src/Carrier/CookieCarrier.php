<?php

declare(strict_types=1);

namespace Spajak\Session\Carrier;

use Spajak\Session\SessionCarrierInterface;
use Spajak\Session\Message;
use LogicException;
use RuntimeException;

class CookieCarrier implements SessionCarrierInterface
{
    protected $options;
    protected $name = 'session';
    protected $cookieSet;

    /**
     * Options:
     *  - An Array of cookie parameters: `name`, `expires`, `path`, `domain`, `secure`, `httponly` and `samesite`.
     * For explanation see [PHP `setcookie()`](https://www.php.net/manual/en/function.setcookie.php).
     */
    public function __construct(array $options = [])
    {
        if (php_sapi_name() === 'cli') {
            throw new LogicException('Cookies cannot be used is PHP CLI mode');
        }
        if (isset($options['name'])) {
            $this->name = (string) $options['name'];
        }
        $defaults = [
            'expires' => null,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict'
        ];
        foreach ($defaults as $name => $value) {
            if (isset($options[$name])) {
                $this->options[$name] = $options[$name];
            } else {
                $this->options[$name] = $value;
            }
        }
    }

    public function fetch() : Message
    {
        $message = new Message;
        if (!isset($_COOKIE[$this->name])) {
            return $message;
        }
        $value = $_COOKIE[$this->name];
        if (is_string($value) and '' !== $value = trim($value)) {
            $message->session = $value;
        }
        return $message;
    }

    public function store(Message $message) : void
    {
        if ($this->cookieSet) {
            throw new LogicException('Session cookie already set');
        }
        if (headers_sent()) {
            throw new LogicException('Cannot set session cookie; HTTP headers already sent');
        }
        $options = $this->options;
        if (!isset($options['expires'])) {
            $options['expires'] = $message->expire ?: 0;
        }
        if (!@setrawcookie($this->name, $message->session, $options)) {
            throw new RuntimeException('Could not set session cookie');
        }
        $this->cookieSet = true;
    }

    public function destroy() : void
    {
        if ($this->cookieSet) {
            throw new LogicException('Session cookie already set');
        }
        if (headers_sent()) {
            throw new LogicException('Cannot expire session cookie; HTTP headers already sent');
        }
        unset($_COOKIE[$this->name]);
        $options = $this->options;
        $options['expires'] = 1;
        if (!@setrawcookie($this->name, '', $options)) {
            throw new RuntimeException('Could not expire session cookie');
        }
        $this->cookieSet = true;
    }
}
