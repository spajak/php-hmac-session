<?php

require __DIR__.'/../vendor/autoload.php';

use Spajak\Session;

$options = [
    'key' => '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18'
];

$session = new Session($options);
$session->set('foo', 'bar')->commit();

echo "Session commited\n";
