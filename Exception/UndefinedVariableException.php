<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class UndefinedVariableException extends IPPException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct("Undefined variable: ". $message, ReturnCode::VARIABLE_ACCESS_ERROR, $previous, false);
    }
}
