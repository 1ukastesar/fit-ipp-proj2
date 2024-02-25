<?php

namespace IPP\Student;

use IPP\Student\Exception\UndefinedFrameException;

/**
 * FrameStack
 * @package IPP\Student
 * Represents a stack of frames
 */
class FrameStack extends Stack {

    /** @var array<array<string, array<string, string>>> */
    protected $stack;

    /**
     * Push a frame to the stack
     * 
     * @param array<string, array<string, string>> $frame
     * @return void
     */
    public function push($frame) {
        array_push($this->stack, $frame);
    }

    /**
     * Pop a frame from the stack
     * 
     * @return array<string, array<string, string>>
     * @throws UndefinedFrameException
     */
    public function pop() {
        if (empty($this->stack)) {
            throw new UndefinedFrameException("Frame stack is empty");
        }
        return array_pop($this->stack);
    }

    /**
     * Get the top frame from the stack
     * 
     * @return array<string, array<string, string>>
     * @throws UndefinedFrameException
     */
    public function top() {
        if (empty($this->stack)) {
            throw new UndefinedFrameException("Frame stack is empty");
        }
        return end($this->stack);
    }
}
