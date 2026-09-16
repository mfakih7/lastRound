<?php

namespace App\Exceptions;

use Exception;

class ActivePackageExistsException extends Exception
{
    public function __construct(public readonly int $remainingSessions)
    {
        parent::__construct(
            "This client still has {$remainingSessions} sessions remaining in the current package.",
        );
    }
}
