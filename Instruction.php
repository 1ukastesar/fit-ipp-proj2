<?php

namespace IPP\Student;

/**
 * Class Instruction
 * @package IPP\Student
 * Represents one instruction from the XML source file
 */
class Instruction
{
    protected string $opcode;
    /** @var array<int<0, max>, array<string, string>> */
    protected $args;

    /**
     * Instruction constructor
     * 
     * Setup a new instruction with given opcode and arguments.
     * @param string $opcode
     * @param array<int<0, max>, array<string, string>> $args
     * @return void
     */
    public function __construct(string $opcode, array $args)
    {
        $this->opcode = $opcode;
        $this->args = $args;
    }

    /**
     * Get instruction opcode
     * 
     * @return string
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Get instruction arguments
     * 
     * @return array<int<0, max>, array<string, string>>
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Convert instruction to its string representation
     * 
     * @return string
     */
    public function __toString()
    {
        $args = [];
        foreach ($this->args as $arg) {
            $type = array_key_first($arg);
            $value = $arg[$type];
            // args[] = "type@value"
            $args[] = $type . '@' . $value;
        }
        // OPCODE arg1 arg2 ...
        return $this->opcode . ' ' . implode(' ', $args);
    }
}
