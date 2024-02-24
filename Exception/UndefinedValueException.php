<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class UndefinedValueException extends IPPException
{
    public function __construct(string $value, ?Throwable $previous = null)
    {
        parent::__construct("Undefined value: ". $value, ReturnCode::VALUE_ERROR, $previous, false);
    }
}
