<?php

namespace IPP\Student;

use IPP\Student\Exception\EmptyStackException;

/**
 * Stack
 * @package IPP\Student
 * Represents a stack data structure
 */
class Stack {

    /** @var array<mixed> $stack */
    private $stack;

    /**
     * Stack constructor
     * 
     * Setup a new empty stack
     * @return void
     */
    public function __construct() {
        $this->stack = [];
    }

    /**
     * Push an item to the stack
     * 
     * @param mixed $item
     * @return void
     */
    public function push($item) {
        array_unshift($this->stack, $item);
    }

    /**
     * Pop an item from the stack
     * 
     * @return mixed
     * @throws EmptyStackException
     */
    public function pop() {
        if($this->isEmpty())
            throw new EmptyStackException();
        return array_shift($this->stack);
    }

    /**
     * Get the top item from the stack
     * 
     * @return mixed
     * @throws EmptyStackException
     */
    public function top() {
        if($this->isEmpty())
            throw new EmptyStackException();
        return current($this->stack);
    }

    /**
     * Check if the stack is empty
     * 
     * @return bool
     */
    public function isEmpty() {
        return empty($this->stack);
    }
}
