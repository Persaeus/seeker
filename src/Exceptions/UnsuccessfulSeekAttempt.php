<?php

namespace Nihilsen\Seeker\Exceptions;

class UnsuccessfulSeekAttempt extends \ErrorException
{
    public function __construct(\Throwable|bool $error)
    {
        $message = 'Unsuccessful seek attempt';

        if ($error instanceof \Throwable) {
            $message .= " with message: '{$error->__toString()}'";
        }

        return parent::__construct($message);
    }
}
