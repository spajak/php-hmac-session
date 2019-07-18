<?php

require __DIR__.'/../vendor/autoload.php';

use Spajak\Session;

$options = [
    'key' => '989c1dc746915cc3e761d002072a74ccdf258b878f37f71080a39a56fa8dfb18'
];

$session = new Session($options);

$_COOKIE[$session->getName()] = 'AAAAAhQCEQNmb28RA2JhchEDYWJjFAMGABEDb25lBgERA3R3bwYCEQV0aHJlZQ==.1563460100.ba9f42d93f57f75bf1d142b3b1f10b4578fbbc1d360a6ce88607884417e3ba29';

//$session->set('foo', 'bar');
//$session->set('abc', ['one', 'two', 'three']);

echo sprintf("Session: %s\n", $session->getSession());
