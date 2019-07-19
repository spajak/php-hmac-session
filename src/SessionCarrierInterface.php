<?php

declare(strict_types=1);

namespace Spajak\Session;

interface SessionCarrierInterface
{
    public function fetch() : Message;

    public function store(Message $message) : void;

    public function destroy() : void;
}
