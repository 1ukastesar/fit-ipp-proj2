<?php

namespace IPP\Student;

use IPP\Student\Exception\UndefinedFrameException;

/**
 * FrameStack
 * @package IPP\Student
 * Extends stack for handling frames (different return codes)
 * @extends Stack<array<string, array<string, string>>>
 */
class FrameStack extends Stack
{
    /**
     * Get the top frame from the stack
     * 
     * @return array<string, array<string, string>>
     * @throws UndefinedFrameException
     */
    public function top()
    {
        if (empty($this->stack)) {
            throw new UndefinedFrameException();
        }
        return end($this->stack);
    }

    /**
     * Pop a frame from the stack
     * 
     * @return array<string, array<string, string>>
     * @throws UndefinedFrameException
     */
    public function pop()
    {
        if (empty($this->stack)) {
            throw new UndefinedFrameException();
        }
        return array_pop($this->stack);
    }
}
