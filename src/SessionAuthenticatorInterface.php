<?php

declare(strict_types=1);

namespace Spajak\Session;

interface SessionAuthenticatorInterface
{
    public function sign(Message $message) : void;

    public function validate(Message $message) : void;
}
