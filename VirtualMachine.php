<?php

namespace IPP\Student;

use IPP\Student\Exception\SemanticError;
use IPP\Student\Exception\UndefinedFrameException;
use IPP\Student\Exception\UndefinedVariableException;
use IPP\Student\Exception\UndefinedValueException;
use IPP\Student\Exception\WrongOperandTypeException;
use IPP\Student\Exception\WrongOperandValueException;

use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;

/**
 * VirtualMachine
 * @package IPP\Student
 * Represents a virtual machine for IPPcode24
 */
class VirtualMachine {

    /** @var array<Instruction> */
    private $instructions;
    /** @var array<string, int> */
    private $labels;
    /** @var InputReader */
    private $input;
    /** @var OutputWriter */
    private $stdout;
    /** @var OutputWriter */
    private $stderr;

    /** @var int */
    private $ip;
    /** @var CallStack */
    private $callStack;
    /** @var FrameStack */
    private $frameStack;
    /** @var Stack */
    private $dataStack;
    /** @var array<string, array<string, string>> */
    private $globalFrame;
    /** @var array<string, array<string, string>>|null */
    private $temporaryFrame;

    /**
     * VirtualMachine constructor
     * 
     * Setup a new virtual machine with given instructions and labels.
     * @param array<Instruction> $instructions
     * @param array<string, int> $labels
     * @param InputReader $input
     * @param OutputWriter $stdout
     * @param OutputWriter $stderr
     * @return void
     */
    public function __construct($instructions, $labels, $input, $stdout, $stderr)
    {
        $this->instructions = $instructions;
        $this->labels = $labels;
        $this->input = $input;
        $this->stdout = $stdout;
        $this->stderr = $stderr;

        $this->ip = intval(array_key_first($instructions));
        $this->callStack = new CallStack();
        $this->frameStack = new FrameStack();
        $this->dataStack = new Stack();
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
        // @phpstan-ignore-next-line
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
        // var_dump($this->globalFrame);
        // var_dump($this->frameStack);
        // var_dump($this->dataStack);
    }

    /**
     * Get variable
     * 
     * Get variable from correct frame based on its name.
     * @param string $name
     * @return array<string, string>
     * @throws UndefinedFrameException
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
                // TODO rewrite this to the more effective variant (use reference instead of copy)
                $lf = $this->frameStack->pop();
                $lf[$name] = ["type" => $type, "value" => $value];
                $this->frameStack->push($lf);
                // $this->frameStack->top()[$name] = ["type" => $type, "value" => $value];
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
     * MOVE <var> <symb>
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
     * DEFVAR <var>
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
                // TODO rewrite this to the more effective variant (use reference instead of copy)
                $lf = $this->frameStack->pop();
                $lf[$name] = ["type" => "nil", "value" => ""];
                $this->frameStack->push($lf);
                // $this->frameStack->top()[$name] = ["type" => "nil", "value" => ""];
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

    /** 
     * CALL <label>
     * 
     * Call a function.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function CALL($args)
    {
        $this->callStack->push($this->ip);
        $this->ip = $this->labels[$args[1]["value"]];
    }

    /** 
     * RETURN
     * 
     * Return from a function.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function RETURN($args)
    {
        $this->ip = $this->callStack->pop();
    }

    /** 
     * PUSHS <symb>
     * 
     * Push a value to the data stack.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function PUSHS($args)
    {
        if ($args[1]["type"] === "var") {
            $var = $this->getVariable($args[1]["value"]);
            $this->dataStack->push($var);
        } else {
            $this->dataStack->push($args[1]);
        }
    }

    /** 
     * POPS <var>
     * 
     * Pop a value from the data stack to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     */
    private function POPS($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        $dstName = $args[1]["value"];
        $src = $this->dataStack->pop();
        $this->setVariable($dstName, $src["type"], $src["value"]);
    }

    /** 
     * ADD <var> <symb1> <symb2>
     * 
     * Add two values and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     * @throws WrongOperandValueException
     */
    private function ADD($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        $dstName = $args[1]["value"];

        if ($args[2]["type"] === "var") {
            $src1 = $this->getVariable($args[2]["value"]);
        } else {
            $src1 = $args[2];
        }

        if ($args[3]["type"] === "var") {
            $src2 = $this->getVariable($args[3]["value"]);
        } else {
            $src2 = $args[3];
        }

        if ($src1["type"] !== "int" || $src2["type"] !== "int") {
            throw new WrongOperandTypeException("Invalid argument type: " . $src1["type"] . " or " . $src2["type"]);
        }

        $value = intval($src1["value"]) + intval($src2["value"]);
        $this->setVariable($dstName, "int", strval($value));
    }

    /**
     * READ <var> <type>
     * 
     * Read a value from stdin to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     * @throws WrongOperandValueException
     * @throws UndefinedVariableException
     * @throws UndefinedFrameException
     */
    private function READ($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException("Invalid argument type: " . $args[1]["type"]);
        }

        $dstName = $args[1]["value"];
        $type = $args[2]["value"];

        switch($type) {
            case "int":
                $value = $this->input->readInt();
                break;
            case "string":
                $value = $this->input->readString();
                break;
            case "bool":
                $value = $this->input->readBool();
                break;
            default:
                throw new WrongOperandValueException("Invalid type: " . $type);
        }

        if ($value === null) {
            $type = "nil";
            $value = "nil";
        }

        $this->setVariable($dstName, $type, strval($value));
    }

    /**
     * WRITE <symb>
     * 
     * Write a value to stdout.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function WRITE($args)
    {
        if ($args[1]["type"] === "var") {
            $var = $this->getVariable($args[1]["value"]);
            $this->stdout->writeString($var["value"]);
        } else {
            $this->stdout->writeString($args[1]["value"]);
        }
    }
}
