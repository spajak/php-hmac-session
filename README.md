# Simple PHP storage-less, HMAC hashed session

No ids, no storage, not locks.

Project is not stable yet! Do not use in production.

# Features
 - **No server storage**. Session is serialized into string, and as a whole travels to the client (using a cookie header).
   The client sends it back to the server (with the next request) where the session is verified and unserialized into PHP array.

 - **Secure**. Session is signed with [HMAC](https://en.wikipedia.org/wiki/HMAC) message authentication code before
   it is sent to the client. Thanks to this we are able to verify both the data integrity and the authentication of a session.
   Session is also stamped with an expire time (as a unix timestamp appended to the serialized session data). Expired session
   is considered invalid and is discarded, but a supplied message is still available for investigation.

 - **Binary serialized**. Session data is serialized into a binary string using one of the two popular binary serializers:
   [`igbinary`](https://github.com/igbinary/igbinary) and [`msgpack`](https://github.com/msgpack/msgpack-php)
   (both are optional, and are available as PHP extensions). Thanks to this serialized session data can be significantly
   smaller and serialization process is generally faster comparing to other serializers.
   Serialization falls back to text form (`json`) in case both binary serializers are not available.

# Limits
 - HTTP Cookie size is limited to **4096** bytes by the browsers. Therefore keep the session data as small as possible.
   Use `Session::getSession()::getSize()` method to get the current session size at any time, if unsure.

# How it works
The session is internally keept as an `array`. Before commit, it is serialized into a string. Then hashed (`sha256`), signed,
and finally it looks like this:

 - `payload.expire.hash` (or `message.hash` in short).

Where:

 - `payload` - Base64 encoded serialized session array.
 - `expire`  - Unix timestamp that tells when the session will expire.
 - `hash`    - HMAC hash of the `payload.expire` string (`payload` __before__ base64 encode).

The string `message.hash` travels to the client and back to the server (with a `set-cookie` response, and `cookie` request header).
On the server side, it is validated for authenticity and expiration. If all is green, the session is unserialized back to PHP array and resumed.

# Usage
```php
use Spajak\Session\Session;
use Spajak\Session\Carrier\CookieCarrier;
use Spajak\Session\Authenticator\HmacAuthenticator;

// Private key
$key = '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18';

// Cookie settings. See [PHP `setcookie` function](https://www.php.net/manual/en/function.setcookie.php)
$cookie = [
    'secure' => false
];

$session = new Session(new CookieCarrier($cookie), new HmacAuthenticator($key));
$session->setTtl(60);

$userId = $session->get('user_id');
$session->set('favourite_vegetable', 'chocolate');

// Session has to be committed explicitly at the end of writting
$session->commit();

```

See also `examples` directory.

# Serializers
 - igbinary [`igbinary`](https://github.com/igbinary/igbinary). Must be compiled as a PHP extension.
 - MessagePack [`msgpack`](https://github.com/msgpack/msgpack-php). Must be compiled as a PHP extension.
 - Json (PHP internal).
 - Add your own!

```php
use Spajak\Session\Serializer\IgbinarySerializer;
use Spajak\Session\Serializer\MessagePackSerializer;
use Spajak\Session\Serializer\JsonSerializer;
// or implement
use Spajak\Session\SessionSerializerInterface;
```

See also my gist: [serializers comparison](https://gist.github.com/spajak/d07a999deb0430e2b6b7e58fc44213d1).

# Documentation

Work in progress..

# License

No idea.
