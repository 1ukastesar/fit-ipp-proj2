<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class WrongOperandTypeException extends IPPException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct("Wrong operand type: ". $message, ReturnCode::OPERAND_TYPE_ERROR, $previous, false);
    }
}
