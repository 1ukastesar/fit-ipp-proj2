<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class WrongOperandValueException extends IPPException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct("Wrong operand value: ". $message, ReturnCode::OPERAND_VALUE_ERROR, $previous, false);
    }
}
