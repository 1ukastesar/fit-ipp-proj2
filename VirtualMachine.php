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
    /** @var Stack<int> */
    private $callStack;
    /** @var Stack<array<string, array<string, string>>> */
    private $frameStack;
    /** @var Stack<array<string, string>> */
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
        $this->callStack = new Stack();
        $this->frameStack = new Stack();
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
     * symb
     * 
     * Resolve and return a value from a variable or a constant.
     * 
     * @param array<string, string> $arg
     * @return array<string, string>
     * @throws WrongOperandTypeException
     */
    private function symb($arg)
    {
        $type = $arg["type"];
        switch($type) {
            case "var":
                return $this->getVariable($arg["value"]);
            case "int":
            case "bool":
            case "string":
            case "nil":
                return $arg;
            default:
                throw new WrongOperandTypeException($type);
        }
    }

    /** 
     * var
     * 
     * Check if argument is a variable.
     * 
     * @param array<string, string> $arg
     * @return array<string, string>
     * @throws WrongOperandTypeException
     */
    private function var($arg)
    {
        if ($arg["type"] !== "var") {
            throw new WrongOperandTypeException($arg["type"]);
        }
        return $arg;
    }

    /** 
     * MOVE <var> <symb>
     * 
     * Move value (from const or another variable) to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function MOVE($args)
    {
        $dst = $this->var($args[1])["value"];
        $src = $this->symb($args[2]);

        $this->setVariable($dst, $src["type"], $src["value"]);
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
     * @throws WrongOperandValueException
     */
    private function DEFVAR($args)
    {
        $var = explode("@", $this->var($args[1])["value"], 2); // Max 2 parts
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
        $this->dataStack->push($this->symb($args[1]));
    }

    /** 
     * POPS <var>
     * 
     * Pop a value from the data stack to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function POPS($args)
    {
        $dst = $this->var($args[1])["value"];
        $src = $this->dataStack->pop();
        // @phpstan-ignore-next-line
        $this->setVariable($dst, $src["type"], $src["value"]);
    }

    /**
     * Check if both symbols are integers.
     * 
     * @param array<string, string> $src1
     * @param array<string, string> $src2
     * @return void
     * @throws WrongOperandTypeException
     */
    private function checkInts($src1, $src2)
    {
        if ($src1["type"] !== "int") {
            throw new WrongOperandTypeException($src1["type"]);
        }

        if ($src2["type"] !== "int") {
            throw new WrongOperandTypeException($src2["type"]);
        }
    }

    /**
     * Convert a symbol to an integer.
     * 
     * @param array<string, string> $arg
     * @return int
     * @throws WrongOperandTypeException
     */
    private function convertToInt($arg)
    {
        if ($arg["type"] !== "int") {
            throw new WrongOperandTypeException($arg["type"]);
        }

        // TODO type checks
        return intval($arg["value"]);
    }

    /** 
     * ADD <var> <symb1> <symb2>
     * 
     * Add two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function ADD($args)
    {
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) + $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * SUB <var> <symb1> <symb2>
     * 
     * Subtract two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function SUB($args)
    {
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) - $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * MUL <var> <symb1> <symb2>
     * 
     * Multiply two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function MUL($args)
    {
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) * $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * IDIV <var> <symb1> <symb2>
     * 
     * Divide two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandValueException
     */
    private function IDIV($args)
    {
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $divisor = $this->convertToInt($src2);
        if ($divisor === 0) {
            throw new WrongOperandValueException("Division by zero");
        }

        $value = intdiv($this->convertToInt($src1), $divisor);
        $this->setVariable($dst, "int", strval($value));
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
     */
    private function READ($args)
    {
        if ($args[1]["type"] !== "var") {
            throw new WrongOperandTypeException($args[1]["type"]);
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
        $this->stdout->writeString($this->symb($args[1])["value"]);
    }

    /**
     * DPRINT <symb>
     * 
     * Write a value to stderr.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     *
     */
    private function DPRINT($args)
    {
        $this->stderr->writeString($this->symb($args[1])["value"] . PHP_EOL);
    }

    /**
     * BREAK
     * 
     * Print the current state of the virtual machine to stderr.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function BREAK($args)
    {
        $this->stderr->writeString("Instruction pointer: " . $this->ip . PHP_EOL);
        $this->stderr->writeString(PHP_EOL);
        $globalFrame    = (empty($this->globalFrame))    ? "Empty" . PHP_EOL : print_r($this->globalFrame, true);
        $temporaryFrame = (empty($this->temporaryFrame)) ? "Empty" . PHP_EOL : print_r($this->temporaryFrame, true);
        $frameStack     = ($this->frameStack->isEmpty()) ? "Empty" . PHP_EOL : print_r($this->frameStack, true);
        $dataStack      = ($this->dataStack->isEmpty())  ? "Empty" . PHP_EOL : print_r($this->dataStack, true);
        $this->stderr->writeString("Global frame: "    . $globalFrame    . PHP_EOL);
        $this->stderr->writeString("Temporary frame: " . $temporaryFrame . PHP_EOL);
        $this->stderr->writeString("Frame stack: "     . $frameStack     . PHP_EOL);
        $this->stderr->writeString("Data stack: "      . $dataStack      . PHP_EOL);
    }
}
