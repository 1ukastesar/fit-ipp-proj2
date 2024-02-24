<?php

namespace IPP\Student;

use IPP\Student\Exception\SemanticError;
use IPP\Student\Exception\UndefinedFrameException;
use IPP\Student\Exception\UndefinedVariableException;
use IPP\Student\Exception\UndefinedValueException;
use IPP\Student\Exception\WrongOperandTypeException;
use IPP\Student\Exception\WrongOperandValueException;

class VirtualMachine {

    /** @var array<Instruction> */
    private $instructions;
    /** @var array<string, int> */
    private $labels;
    /** @var int */
    private $ip;
    /** @var Stack */
    private $callStack;
    /** @var FrameStack */
    private $frameStack;
    /** @var array<string, array<string, string>>|null */
    private $temporaryFrame;
    /** @var array<string, array<string, string>> */
    private $globalFrame;

    /**
     * VirtualMachine constructor
     * 
     * Setup a new virtual machine with given instructions and labels.
     * @param array<Instruction> $instructions
     * @param array<string, int> $labels
     * @return void
     */
    public function __construct($instructions, $labels) 
    {
        $this->instructions = $instructions;
        $this->labels = $labels;
        $this->ip = intval(array_key_first($instructions));
        $this->callStack = new Stack();
        $this->frameStack = new FrameStack();
        $this->globalFrame = [];
        $this->temporaryFrame = null;
    }

    /**
     * Execute instruction
     * 
     * Execute given instruction.
     * @param Instruction $instruction
     * @return void
     */
    private function executeInstruction($instruction)
    {
        call_user_func(array($this, strtoupper($instruction->getOpcode())), $instruction->getArgs());
    }

    /**
     * Run virtual machine
     * 
     * Start executing instructions.
     * @return void
     */
    public function run()
    {
        while ($this->ip < count($this->instructions)) {
            $instruction = $this->instructions[$this->ip];
            $this->executeInstruction($instruction);
            $this->ip++;
        }
        var_dump($this->globalFrame);
        var_dump($this->frameStack);
    }

    /**
     * Get variable
     * 
     * Get variable from correct frame based on its name.
     * @param string $name
     * @return array<string, string>
     * @throws UndefinedVariableException
     * @throws WrongOperandValueException
     */
    private function getVariable($name)
    {
        $var = explode("@", $name, 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (!array_key_exists($name, $this->globalFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $var = $this->globalFrame[$name];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (!array_key_exists($name, $this->frameStack->top())) {
                    throw new UndefinedVariableException($name);
                }
                $var = ($this->frameStack->top())[$name];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame)) {
                    throw new UndefinedFrameException();
                }
                if (!array_key_exists($name, $this->temporaryFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $var = $this->temporaryFrame[$name];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
        if ($var["type"] === "nil" && $var["value"] === "") {
            throw new UndefinedValueException($name);
        }
        return $var;
    }

    /**
     * Set variable
     * 
     * Set variable in correct frame based on its name.
     * @param string $name
     * @param string $type
     * @param string $value
     * @return void
     * @throws UndefinedFrameException
     * @throws UndefinedVariableException
     * @throws WrongOperandValueException
     */
    private function setVariable($name, $type, $value)
    {
        $var = explode("@", $name, 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (!array_key_exists($name, $this->globalFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $this->globalFrame[$name] = ["type" => $type, "value" => $value];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (!array_key_exists($name, $this->frameStack->top())) {
                    throw new UndefinedVariableException($name);
                }
                $this->frameStack->top()[$name] = ["type" => $type, "value" => $value];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame)) {
                    throw new UndefinedFrameException();
                }
                if (!array_key_exists($name, $this->temporaryFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $this->temporaryFrame[$name] = ["type" => $type, "value" => $value];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
    }

    /** 
     * MOVE
     * 
     * Move value (from const or another variable) to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     */
    private function MOVE($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        $dstName = $args[1]["value"];

        if ($args[2]["type"] === "var") {
            $src = $this->getVariable($args[2]["value"]);
        } else {
            $src = $args[2];
        }

        $this->setVariable($dstName, $src["type"], $src["value"]);
    }

    /** 
     * CREATEFRAME
     * 
     * Create a new temporary frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function CREATEFRAME($args)
    {
        $this->temporaryFrame = [];
    }

    /** 
     * PUSHFRAME
     * 
     * Push temporary frame to the frame stack.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws UndefinedFrameException
     */
    private function PUSHFRAME($args)
    {
        if(!is_array($this->temporaryFrame)) {
            throw new UndefinedFrameException();
        }
        $this->frameStack->push($this->temporaryFrame);
        $this->temporaryFrame = null;
    }

    /** 
     * POPFRAME
     * 
     * Pop frame from the frame stack to the temporary frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function POPFRAME($args)
    {
        $this->temporaryFrame = $this->frameStack->pop();
    }

    /** 
     * DEFVAR
     * 
     * Define a new variable in the correct frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws SemanticError
     * @throws UndefinedFrameException
     * @throws WrongOperandTypeException
     * @throws WrongOperandValueException
     */
    private function DEFVAR($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        $var = explode("@", $args[1]["value"], 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (array_key_exists($name, $this->globalFrame)) {
                    throw new SemanticError($name);
                }
                $this->globalFrame[$name] = ["type" => "nil", "value" => ""];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (array_key_exists($name, (array) $this->frameStack->top())) {
                    // Variable redefinition is semantic error
                    // weird but ok
                    throw new SemanticError($name);
                }
                $this->frameStack->top()[$name] = ["type" => "nil", "value" => ""];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame))
                    throw new UndefinedFrameException();
                if (array_key_exists($name, $this->temporaryFrame)) {
                    throw new SemanticError($name);
                }
                $this->temporaryFrame[$name] = ["type" => "nil", "value" => ""];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
    }
}
