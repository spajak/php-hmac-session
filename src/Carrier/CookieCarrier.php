<?php

declare(strict_types=1);

namespace Spajak\Session\Carrier;

use Spajak\Session\SessionCarrierInterface;
use DomainException;
use RuntimeException;

class CookieCarrier implements SessionCarrierInterface
{
    protected $options;

    /**
     * Options:
     *  - An Array of cookie parameters: `expires`, `path`, `domain`, `secure`, `httponly` and `samesite`.
     * For explanation see [PHP `setcookie()`](https://www.php.net/manual/en/function.setcookie.php).
     */
    public function __construct(array $options = [])
    {
        if (php_sapi_name() === 'cli') {
            throw new DomainException('Cookies cannot be used is PHP CLI mode');
        }
        $defaults = [
            'name' => 'session',
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => false,
            'samesite' => true
        ];
        $this->options = array_merge($defaults, $options);
    }

    public function fetch() : ?string
    {
        if (!isset($_COOKIE[$this->options['name']])) {
            return null;
        }
        $value = $_COOKIE[$this->options['name']];
        if (!is_string($value) or '' === trim($value)) {
            return null;
        }
        return trim($value);
    }

    public function store(string $data, int $ttl = 0) : void
    {
        if (headers_sent()) {
            throw new DomainException('Cannot set session cookie; HTTP headers already sent');
        }
        $options = $this->options;
        if (!isset($options['expires'])) {
            $options['expires'] = $ttl > 0 ? time() + $ttl : 0;
        }
        if (!setrawcookie($options['name'], $data, $options)) {
            throw new RuntimeException('Could not set session cookie');
        }
    }

    public function destroy() : void
    {
        if (headers_sent()) {
            throw new DomainException('Cannot expire session cookie; HTTP headers already sent');
        }
        $options = $this->options;
        unset($_COOKIE[$options['name']]);
        $options['expires'] = time() - 60*60*24;
        if (!setrawcookie($options['name'], '', $options)) {
            throw new RuntimeException('Could not expire session cookie');
        }
    }
}
