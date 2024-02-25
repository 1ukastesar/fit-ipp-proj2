<?php

namespace IPP\Student;

use IPP\Student\Exception\EmptyStackException;

/**
 * CallStack
 * @package IPP\Student
 * Represents a stack of IPs
 */
class CallStack extends Stack {

    /** @var array<int> */
    protected $stack;

    /**
     * Push an IP to the stack
     * 
     * @param int $ip
     * @return void
     */
    public function push($ip) {
        array_push($this->stack, $ip);
    }

    /**
     * Pop an IP from the stack
     * 
     * @return int
     * @throws EmptyStackException
     */
    public function pop() {
        if (empty($this->stack)) {
            throw new EmptyStackException();
        }
        return array_pop($this->stack);
    }

    /**
     * Get the top IP from the stack
     * 
     * @return int
     * @throws EmptyStackException
     */
    public function top() {
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
    public function isEmpty() {
        return empty($this->stack);
    }
}
