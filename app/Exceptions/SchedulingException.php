<?php

namespace App\Exceptions;

use RuntimeException;

class SchedulingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $field = null,
    ) {
        parent::__construct($message);
    }
}
