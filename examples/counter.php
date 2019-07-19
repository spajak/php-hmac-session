<?php

# Try it: php -S localhost:8080 examples/counter.php

require __DIR__.'/../vendor/autoload.php';

use Spajak\Session\Session;
use Spajak\Session\Carrier\CookieCarrier;
use Spajak\Session\Authenticator\HmacAuthenticator;

if (false !== strpos($_SERVER['REQUEST_URI'], 'favicon.ico')) {
    return false;
}

$key = '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18';

$cookie = [
    'secure' => false
];

$session = new Session(new CookieCarrier($cookie), new HmacAuthenticator($key));
$session->setTtl(10);

$count = $session->get('count', 1);
$session->set('count', $count+1)->commit();

header('content-type: text/plain; charset=utf-8');
?>
This is your <?= $count ?> visit in a row. Wait 10 seconds to reset
