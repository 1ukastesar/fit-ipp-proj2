<?php

namespace IPP\Student;

use IPP\Student\Exception\EmptyStackException;

/**
 * Stack
 * @package IPP\Student
 * Represents a stack data structure
 * @template T
 */
class Stack {

    /** @var array<T> */
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
     * @param T $item
     * @return void
     */
    public function push($item)
    {
        array_push($this->stack, $item);
    }

    /**
     * Pop an item from the stack
     * 
     * @return T
     * @throws EmptyStackException
     */
    public function pop()
    {
        if (empty($this->stack)) {
            throw new EmptyStackException();
        }
        return array_pop($this->stack);
    }

    /**
     * Get the top item from the stack
     * 
     * @return T
     * @throws EmptyStackException
     */
    public function top()
    {
        if (empty($this->stack)) {
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




