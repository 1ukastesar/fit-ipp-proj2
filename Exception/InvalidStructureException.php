<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class InvalidStructureException extends IPPException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct("Invalid source XML structure: ". $message, ReturnCode::INVALID_SOURCE_STRUCTURE, $previous);
    }
}
