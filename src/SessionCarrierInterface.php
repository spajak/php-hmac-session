<?php

declare(strict_types=1);

namespace Spajak\Session;

/**
 * Session carrier interface.
 */
interface SessionCarrierInterface
{
    /**
     * Get raw session data from some source (eg. cookie).
     */
    public function fetch() : Message;

    /**
     * Place session data somewhere (eg. cookie)
     */
    public function store(Message $message) : void;

    /**
     * Completely destroy this session.
     */
    public function destroy() : void;
}
