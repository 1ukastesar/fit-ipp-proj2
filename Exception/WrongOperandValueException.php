<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class WrongOperandValueException extends IPPException
{
    public function __construct(string $value, ?Throwable $previous = null)
    {
        parent::__construct("Wrong operand value: ". $value, ReturnCode::OPERAND_VALUE_ERROR, $previous);
    }
}
