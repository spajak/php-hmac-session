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

$data = [
    "foo" => "bar",
    "session" => [
        "user_id" => 12942834234,
        "roles" => ["user", "member", "voter"],
        "likes" => ["football", "beer", "couch"]
    ],
    "abc" => ['one', 2, 'three hundred']
];

// Session ---------------------------------------------------------------------
$session = new Session(new MemoryCarrier, new HmacAuthenticator($key));
$authenticator = $session->getAuthenticator();
$serializer = $session->getSerializer();
$session = $session->getSession();
$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    $session->payload = $serializer->serialize($data);
    $authenticator->sign($session);
}
$size = $session->getSize();
$t1 = millitime() - $t;
$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    $authenticator->validate($session);
    $serializer->unserialize($session->payload);
}
$t2 = millitime() - $t;

echo sprintf("Session: %s bytes in %s ms and %s ms\n", $size, $t1, $t2);

// JWT -------------------------------------------------------------------------
$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    jwt_encode($data, $key, 'HS256');
}
$value = jwt_encode($data, $key, 'HS256');
$size = strlen($value);
$t1 = millitime() - $t;
$t = millitime();
for ($i = 0; $i < ITERATIONS; $i++) {
    jwt_decode($value, $key, 'HS256');
}
$t2 = millitime() - $t;

echo sprintf("JWT: %s bytes in %s ms and %s ms\n", $size, $t1, $t2);

echo "Keep in mind \"jwt\" is a C extension\n";
