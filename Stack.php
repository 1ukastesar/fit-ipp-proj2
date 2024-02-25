<?php

namespace IPP\Student;

use IPP\Student\Exception\EmptyStackException;
use IPP\Student\Exception\UndefinedFrameException;

/**
 * Stack
 * @package IPP\Student
 * Represents a stack data structure
 */
class Stack {

    /** @var array<mixed> */
    protected $stack;

    /**
     * Stack constructor
     * 
     * Setup a new empty stack
     * @return void
     */
    public function __construct()
    {
        $this->stack = [];
    }

    /**
     * Push an item to the stack
     * 
     * @param mixed $item
     * @return void
     */
    public function push($item)
    {
        array_push($this->stack, $item);
    }

    /**
     * Pop an item from the stack
     * 
     * @return mixed
     * @throws EmptyStackException
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new EmptyStackException();
        }
        return array_pop($this->stack);
    }

    /**
     * Get the top item from the stack
     * 
     * @return mixed
     * @throws EmptyStackException
     */
    public function top()
    {
        if ($this->isEmpty()) {
            throw new EmptyStackException();
        }
        return end($this->stack);
    }

    /**
     * Check if the stack is empty
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->stack);
    }
}

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
