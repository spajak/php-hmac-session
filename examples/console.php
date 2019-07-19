<?php

require __DIR__.'/../vendor/autoload.php';

use Spajak\Session\Session;
use Spajak\Session\Carrier\MemoryCarrier;
use Spajak\Session\Authenticator\HmacAuthenticator;

$key = '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18';

$input = null;
if (isset($argv[1])) {
    $input = $argv[1];
}

$session = new Session(new MemoryCarrier($input), new HmacAuthenticator($key));

if ($input) {
    $previous = $session->getLoadedSession();
    if ($previous->valid) {
        echo sprintf("Loaded session (%s): %s\n", $previous->getSize(), $previous);
        var_dump($session->getData());
    } else {
        echo sprintf("Loaded session is not valid: %s\n", $previous);
    }
} else {
    $session
        ->set('foo', 'bar')
        ->set('abc', ['one', 2, 'three'])
        ->commit();

    echo sprintf("New session (%s): %s\n", $session->getSession()->getSize(), $session->getSession());
}
