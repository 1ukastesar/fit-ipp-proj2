<?php

namespace IPP\Student;

use IPP\Student\Exception\UndefinedVariableException;
use IPP\Student\Exception\WrongOperandTypeException;
use IPP\Student\Exception\WrongOperandValueException;

class VirtualMachine {

    private $instructions;
    private $labels;
    private $ip;
    private $callStack;
    private $frameStack;
    private $temporaryFrame;
    private $globalFrame;

    public function __construct($instructions, $labels) 
    {
        $this->instructions = $instructions;
        $this->labels = $labels;
        $this->ip = array_key_first($instructions);
        $this->callStack = new Stack();
        $this->frameStack = new Stack();
    }

    private function executeInstruction($instruction)
    {
        $this->$instruction->opcode->strtoupper($this->$instruction->getArgs());
    }

    public function run()
    {
        while ($this->ip < count($this->instructions)) {
            $instruction = $this->instructions[$this->ip];
            $this->executeInstruction($instruction);
        }
    }

    private function MOVE($args)
    {
        if ($args[1][0] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        if($args[2][0] === "var")
            $src = 

        $dst = explode("@", $args[1]["var"], 2); // Max 2 parts
        $frame = $dst[0];
        $name = $dst[1];
        switch($frame) {
            case "GF":
                if (!isset($this->globalFrame[$name])) {
                    throw new UndefinedVariableException("Variable not defined: " . $name);
                }
                $this->globalFrame[$name] = $args[2];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (!isset($this->frameStack->top()[$name])) {
                    throw new UndefinedVariableException("Variable not defined: " . $name);
                }
                $this->frameStack->top()[$name] = $args[2];
                break;
            case "TF":
                if (!isset($this->temporaryFrame[$name])) {
                    throw new UndefinedVariableException("Variable not defined: " . $name);
                }
                $this->temporaryFrame[$name] = $args[2];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
    }
}
