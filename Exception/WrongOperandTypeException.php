<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

use Throwable;

class WrongOperandTypeException extends IPPException
{
    public function __construct(string $type, ?Throwable $previous = null)
    {
        parent::__construct("Wrong operand type: ". $type, ReturnCode::OPERAND_TYPE_ERROR, $previous, false);
    }
}
