<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class StringOperationException extends IPPException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct("String manipulation error: ". $message, ReturnCode::STRING_OPERATION_ERROR, $previous);
    }
}
