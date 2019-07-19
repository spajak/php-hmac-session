<?php

require __DIR__.'/../vendor/autoload.php';

# [PHP extension for JSON Web Token](https://github.com/cdoco/php-jwt)
if (!extension_loaded('jwt')) {
    throw new DomainException('Required PHP module "jwt" is not loaded');
}

use Spajak\Session\Session;
use Spajak\Session\Carrier\MemoryCarrier;
use Spajak\Session\Authenticator\HmacAuthenticator;

define('ITERATIONS', 1000000);

function millitime() {
    return (int) (microtime(true)*1000);
}

$key = '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18';

$session = new Session(new MemoryCarrier, new HmacAuthenticator($key));
$session
    ->set('foo', 'bar')
    ->set('abc', ['one', 2, 'three']);

$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    $value = $session->getSession();
}
echo sprintf("Session: %s bytes in %s ms\n", strlen($value->getSize()), millitime() - $t);

$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    $value = jwt_encode($session->getData(), $key, 'HS256');
}
echo sprintf("JWT: %s bytes in %s ms\n", strlen($value), millitime() - $t);
