<?php

declare(strict_types=1);

namespace Spajak\Session;

/**
 * Authenticate and sign the session.
 */
interface SessionAuthenticatorInterface
{
    /**
     * Hash and sign the session payload.
     */
    public function sign(Message $message) : void;

    /**
     * Validate and split the session into payload and expire time.
     */
    public function validate(Message $message) : void;
}
